<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use App\Models\User;
use App\Services\ColumnVisibilityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Setup\Models\City;
use Modules\Setup\Models\Lookup;

/**
 * Owns all reads/writes of the tenant `cities` table. Cities are added by picking
 * entries from the central `lookups` master (type=city, restricted-to-master).
 * The picked entry's own fields are snapshotted and linked to its parent tenant
 * state via `state_id` — the state (and its country) are auto-created on pick if
 * absent. The country is reached via state→country. Parent data is read through
 * relations, never duplicated. Identity fields are read-only; only tenant-owned
 * columns may be updated, further gated by the per-user column-visibility matrix.
 */
class CityService
{
    public function __construct(
        private readonly ColumnVisibilityService $columns,
        private readonly StateService $states,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, City>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $stateId = isset($filters['state_id']) ? (int) $filters['state_id'] : null;
        $countryId = isset($filters['country_id']) ? (int) $filters['country_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return City::query()
            ->with('state.country') // avoid N+1 when the resource reads state/country names
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($stateId !== null, fn ($query) => $query->where('state_id', $stateId))
            ->when(
                $countryId !== null,
                fn ($query) => $query->whereHas('state', fn ($state) => $state->where('country_id', $countryId)),
            )
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower((string) $search).'%']),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): City
    {
        /** @var City $city */
        $city = City::query()->with('state.country')->findOrFail($id);

        return $city;
    }

    /**
     * Add cities by picking master `lookup_id`s (type=city). Each is snapshotted and
     * linked to its parent tenant state (auto-created with its country if absent).
     * Rejects ids absent from the master and ids already added; restores a
     * soft-deleted match instead of duplicating.
     *
     * @param  list<int>  $lookupIds
     * @return Collection<int, City>
     */
    public function createFromLookups(array $lookupIds): Collection
    {
        $userId = Auth::guard('api')->id();

        /** @var Collection<int, Lookup> $lookups */
        $lookups = Lookup::query()
            ->where('type', Lookup::TYPE_CITY)
            ->whereIn('id', $lookupIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($lookupIds, $lookups, $userId): Collection {
            /** @var Collection<int, City> $created */
            $created = new Collection;

            foreach ($lookupIds as $lookupId) {
                $lookup = $lookups->get($lookupId);

                if (! $lookup instanceof Lookup) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "City [{$lookupId}] is not in the master list.",
                    ]);
                }

                // Auto-provision the parent state (which auto-provisions its country).
                $stateId = $this->states->resolveFromLookup((int) $lookup->parent_id)->getKey();

                if (City::query()->where('lookup_id', $lookupId)->exists()) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "City [{$lookup->name}] has already been added.",
                    ]);
                }

                /** @var City|null $trashed */
                $trashed = City::onlyTrashed()->where('lookup_id', $lookupId)->latest('id')->first();

                if ($trashed instanceof City) {
                    $trashed->restore();
                    $created->push($trashed);

                    continue;
                }

                /** @var City $city */
                $city = City::query()->create(
                    $this->snapshotAttributes($lookup, $stateId) + ['created_by' => $userId],
                );

                $created->push($city);
            }

            return $created;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, City>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('cities', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, City> $updated */
            $updated = new Collection;

            foreach ($rows as $row) {
                $id = (string) $row['id'];
                $attributes = Arr::except($row, ['id']);

                if ($writable !== null) {
                    $disallowed = array_diff(array_keys($attributes), $writable);

                    if ($disallowed !== []) {
                        throw ValidationException::withMessages([
                            'fields' => 'You are not permitted to edit: '.implode(', ', $disallowed).'.',
                        ]);
                    }
                }

                $city = $this->find($id);
                $city->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($city->getKey());
                }

                $city->save();
                $updated->push($city);
            }

            return $updated;
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, City> $cities */
            $cities = City::query()->whereIn('id', $ids)->get();

            foreach ($cities as $city) {
                $city->delete();
            }

            return $cities->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, City> $cities */
            $cities = City::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($cities as $city) {
                $city->forceDelete();
            }

            return $cities->count();
        });
    }

    /**
     * Import cities from a CSV file. Rows are keyed by `external_id` and matched to
     * the master (type=city); matched rows are upserted, auto-provisioning the
     * parent state + country. Off-master rows are reported as failures and skipped.
     *
     * @return array{imported: int, updated: int, skipped: int, failures: list<array{row: int, errors: list<string>}>}
     */
    public function importCsv(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);
        $userId = Auth::guard('api')->id();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        /** @var list<array{row: int, errors: list<string>}> $failures */
        $failures = [];

        DB::transaction(function () use ($rows, $userId, &$imported, &$updated, &$skipped, &$failures): void {
            foreach ($rows as $row) {
                $externalId = trim((string) ($row['data']['external_id'] ?? ''));

                if ($externalId === '') {
                    $skipped++;

                    continue;
                }

                $lookup = Lookup::query()
                    ->where('type', Lookup::TYPE_CITY)
                    ->where('external_id', $externalId)
                    ->first();

                if (! $lookup instanceof Lookup) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["City '{$externalId}' is not in the master list."]];

                    continue;
                }

                $tenantAttributes = $this->tenantAttributesFromCsv($row['data']);

                /** @var City|null $existing */
                $existing = City::query()->where('lookup_id', $lookup->id)->first();

                if ($existing instanceof City) {
                    $existing->fill($tenantAttributes)->save();
                    $updated++;
                } else {
                    $stateId = $this->states->resolveFromLookup((int) $lookup->parent_id)->getKey();
                    City::query()->create(
                        $this->snapshotAttributes($lookup, $stateId) + $tenantAttributes + ['created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, City>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $stateId = isset($filters['state_id']) ? (int) $filters['state_id'] : null;

        /** @var Collection<int, City> $cities */
        $cities = City::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($stateId !== null, fn ($query) => $query->where('state_id', $stateId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $cities;
    }

    /**
     * Snapshot the master city entry's own fields, linked to its parent tenant
     * state via `state_id`.
     *
     * @return array<string, mixed>
     */
    private function snapshotAttributes(Lookup $lookup, int $stateId): array
    {
        return [
            'lookup_id' => $lookup->id,
            'external_id' => $lookup->external_id,
            'name' => $lookup->name,
            'state_id' => $stateId,
            'latitude' => $lookup->latitude,
            'longitude' => $lookup->longitude,
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function tenantAttributesFromCsv(array $data): array
    {
        $attributes = [];

        if (array_key_exists('is_active', $data) && $data['is_active'] !== '') {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== '') {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        return $attributes;
    }

    /**
     * @return list<array{row: int, data: array<string, string>}>
     */
    private function readCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false || ($handle = fopen($path, 'rb')) === false) {
            throw ValidationException::withMessages(['file' => 'Unable to read the uploaded file.']);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The file is empty.']);
        }

        $header = array_map(static fn ($column): string => mb_strtolower(trim((string) $column)), $header);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];

        if (! in_array('external_id', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain an "external_id" column.']);
        }

        $rows = [];
        $rowNumber = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($cells === [null]) {
                continue;
            }

            /** @var array<string, string> $data */
            $data = [];
            foreach ($header as $index => $name) {
                $data[$name] = (string) ($cells[$index] ?? '');
            }

            $rows[] = ['row' => $rowNumber, 'data' => $data];
        }

        fclose($handle);

        return $rows;
    }

    private function clearOtherDefaults(int $exceptId): void
    {
        City::query()
            ->where('id', '!=', $exceptId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function actingUser(): User
    {
        $user = Auth::guard('api')->user();

        if (! $user instanceof User) {
            throw new \RuntimeException('No authenticated user resolved for column write-gating.');
        }

        return $user;
    }
}
