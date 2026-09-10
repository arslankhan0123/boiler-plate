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
use Modules\Setup\Models\Lookup;
use Modules\Setup\Models\State;

/**
 * Owns all reads/writes of the tenant `states` table. States are added by picking
 * entries from the central `lookups` master (type=state, restricted-to-master).
 * The picked entry's own fields are snapshotted and linked to its parent tenant
 * country via `country_id` — the country is auto-created on pick if absent. Parent
 * data is read through the `country` relation, never duplicated. Identity fields
 * are read-only; only tenant-owned columns may be updated, further gated by the
 * per-user column-visibility matrix.
 */
class StateService
{
    public function __construct(
        private readonly ColumnVisibilityService $columns,
        private readonly CountryService $countries,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, State>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $countryId = isset($filters['country_id']) ? (int) $filters['country_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return State::query()
            ->with('country') // avoid N+1 when the resource reads country name/iso2
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($countryId !== null, fn ($query) => $query->where('country_id', $countryId))
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where(function ($query) use ($search): void {
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$term]);
                }),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): State
    {
        /** @var State $state */
        $state = State::query()->with('country')->findOrFail($id);

        return $state;
    }

    /**
     * Add states by picking master `lookup_id`s (type=state). Each is snapshotted
     * and linked to its parent tenant country (auto-created if absent). Rejects ids
     * absent from the master and ids already added; restores a soft-deleted match
     * instead of duplicating.
     *
     * @param  list<int>  $lookupIds
     * @return Collection<int, State>
     */
    public function createFromLookups(array $lookupIds): Collection
    {
        $userId = Auth::guard('api')->id();

        /** @var Collection<int, Lookup> $lookups */
        $lookups = Lookup::query()
            ->where('type', Lookup::TYPE_STATE)
            ->whereIn('id', $lookupIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($lookupIds, $lookups, $userId): Collection {
            /** @var Collection<int, State> $created */
            $created = new Collection;

            foreach ($lookupIds as $lookupId) {
                $lookup = $lookups->get($lookupId);

                if (! $lookup instanceof Lookup) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "State [{$lookupId}] is not in the master list.",
                    ]);
                }

                // Auto-provision the parent country (find-or-create) so country_id holds.
                $countryId = $this->countries->resolveFromLookup((int) $lookup->parent_id)->getKey();

                if (State::query()->where('lookup_id', $lookupId)->exists()) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "State [{$lookup->name}] has already been added.",
                    ]);
                }

                /** @var State|null $trashed */
                $trashed = State::onlyTrashed()->where('lookup_id', $lookupId)->latest('id')->first();

                if ($trashed instanceof State) {
                    $trashed->restore();
                    $created->push($trashed);

                    continue;
                }

                /** @var State $state */
                $state = State::query()->create(
                    $this->snapshotAttributes($lookup, $countryId) + ['created_by' => $userId],
                );

                $created->push($state);
            }

            return $created;
        });
    }

    /**
     * Find-or-create the tenant state for a master state `lookup_id`, ensuring its
     * parent country exists first — used to auto-provision ancestry when a city is
     * picked. Restores a soft-deleted match; never errors on "already added".
     */
    public function resolveFromLookup(int $stateLookupId): State
    {
        /** @var State|null $existing */
        $existing = State::withTrashed()->where('lookup_id', $stateLookupId)->latest('id')->first();

        if ($existing instanceof State) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        /** @var Lookup $lookup */
        $lookup = Lookup::query()->where('type', Lookup::TYPE_STATE)->findOrFail($stateLookupId);
        $countryId = $this->countries->resolveFromLookup((int) $lookup->parent_id)->getKey();

        return State::query()->create(
            $this->snapshotAttributes($lookup, $countryId) + ['created_by' => Auth::guard('api')->id()],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, State>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('states', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, State> $updated */
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

                $state = $this->find($id);
                $state->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($state->getKey());
                }

                $state->save();
                $updated->push($state);
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
            /** @var Collection<int, State> $states */
            $states = State::query()->whereIn('id', $ids)->get();

            foreach ($states as $state) {
                $state->delete();
            }

            return $states->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, State> $states */
            $states = State::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($states as $state) {
                $state->forceDelete();
            }

            return $states->count();
        });
    }

    /**
     * Import states from a CSV file. Rows are keyed by `external_id` and matched to
     * the master (type=state); matched rows are upserted, auto-provisioning the
     * parent country. Off-master rows are reported as failures and skipped.
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
                    ->where('type', Lookup::TYPE_STATE)
                    ->where('external_id', $externalId)
                    ->first();

                if (! $lookup instanceof Lookup) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["State '{$externalId}' is not in the master list."]];

                    continue;
                }

                $tenantAttributes = $this->tenantAttributesFromCsv($row['data']);

                /** @var State|null $existing */
                $existing = State::query()->where('lookup_id', $lookup->id)->first();

                if ($existing instanceof State) {
                    $existing->fill($tenantAttributes)->save();
                    $updated++;
                } else {
                    $countryId = $this->countries->resolveFromLookup((int) $lookup->parent_id)->getKey();
                    State::query()->create(
                        $this->snapshotAttributes($lookup, $countryId) + $tenantAttributes + ['created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, State>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $countryId = isset($filters['country_id']) ? (int) $filters['country_id'] : null;

        /** @var Collection<int, State> $states */
        $states = State::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($countryId !== null, fn ($query) => $query->where('country_id', $countryId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $states;
    }

    /**
     * Snapshot the master state entry's own fields, linked to its parent tenant
     * country via `country_id`.
     *
     * @return array<string, mixed>
     */
    private function snapshotAttributes(Lookup $lookup, int $countryId): array
    {
        return [
            'lookup_id' => $lookup->id,
            'external_id' => $lookup->external_id,
            'name' => $lookup->name,
            'code' => $lookup->code,
            'country_id' => $countryId,
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
        State::query()
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
