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
use Modules\Setup\Models\Country;
use Modules\Setup\Models\Lookup;

/**
 * Owns all reads/writes of the tenant `countries` table. Countries are added by
 * picking entries from the central `lookups` master (restricted-to-master); the
 * picked entry's fields are snapshotted into the tenant row. ISO/identity fields
 * are read-only — only the tenant-owned columns may be updated, further gated by
 * the per-user column-visibility matrix.
 */
class CountryService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Country>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $region = isset($filters['region']) ? (string) $filters['region'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Country::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($region !== null && $region !== '', fn ($query) => $query->where('region', $region))
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where(function ($query) use ($search): void {
                    // Case-insensitive (Postgres LIKE is case-sensitive); portable LOWER().
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(iso2) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(iso3) LIKE ?', [$term]);
                }),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): Country
    {
        /** @var Country $country */
        $country = Country::query()->findOrFail($id);

        return $country;
    }

    /**
     * Add countries by picking master `lookup_id`s. Each is snapshotted into a new
     * tenant row. Rejects ids absent from the master and ids already added.
     *
     * @param  list<int>  $lookupIds
     * @return Collection<int, Country>
     */
    public function createFromLookups(array $lookupIds): Collection
    {
        $userId = Auth::guard('api')->id();

        /** @var Collection<int, Lookup> $lookups */
        $lookups = Lookup::query()
            ->where('type', Lookup::TYPE_COUNTRY)
            ->whereIn('id', $lookupIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($lookupIds, $lookups, $userId): Collection {
            /** @var Collection<int, Country> $created */
            $created = new Collection;

            foreach ($lookupIds as $lookupId) {
                $lookup = $lookups->get($lookupId);

                if (! $lookup instanceof Lookup) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "Country [{$lookupId}] is not in the master list.",
                    ]);
                }

                if (Country::query()->where('lookup_id', $lookupId)->exists()) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "Country [{$lookup->name}] has already been added.",
                    ]);
                }

                // Re-adding a previously removed country restores its trashed row
                // (keeping the same id, audit lineage, and prior tenant edits)
                // instead of creating a duplicate — `lookup_id` is not unique.
                /** @var Country|null $trashed */
                $trashed = Country::onlyTrashed()->where('lookup_id', $lookupId)->latest('id')->first();

                if ($trashed instanceof Country) {
                    $trashed->restore();
                    $created->push($trashed);

                    continue;
                }

                /** @var Country $country */
                $country = Country::query()->create(
                    $this->snapshotAttributes($lookup) + ['created_by' => $userId],
                );

                $created->push($country);
            }

            return $created;
        });
    }

    /**
     * Find-or-create the tenant country for a master country `lookup_id` — used to
     * auto-provision ancestry when a state/city is picked. Restores a soft-deleted
     * match and never errors on "already added" (unlike the explicit pick above).
     */
    public function resolveFromLookup(int $countryLookupId): Country
    {
        /** @var Country|null $existing */
        $existing = Country::withTrashed()->where('lookup_id', $countryLookupId)->latest('id')->first();

        if ($existing instanceof Country) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        /** @var Lookup $lookup */
        $lookup = Lookup::query()->where('type', Lookup::TYPE_COUNTRY)->findOrFail($countryLookupId);

        return Country::query()->create(
            $this->snapshotAttributes($lookup) + ['created_by' => Auth::guard('api')->id()],
        );
    }

    /**
     * Update the tenant-owned fields of countries. Each row carries its `id`; only
     * columns the user may write (editable ∩ visible) are accepted — others 422.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Country>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('countries', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Country> $updated */
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

                $country = $this->find($id);
                $country->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($country->getKey());
                }

                $country->save();
                $updated->push($country);
            }

            return $updated;
        });
    }

    /**
     * Soft-delete countries by id. Deletes via model instances so the
     * `deleted_by_user_id` stamp and model events fire.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Country> $countries */
            $countries = Country::query()->whereIn('id', $ids)->get();

            foreach ($countries as $country) {
                $country->delete();
            }

            return $countries->count();
        });
    }

    /**
     * Permanently remove countries — only those already soft-deleted are eligible.
     *
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Country> $countries */
            $countries = Country::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($countries as $country) {
                $country->forceDelete();
            }

            return $countries->count();
        });
    }

    /**
     * Import countries from a CSV file. Rows are keyed by `iso2` and matched to the
     * master (restricted-to-master); matched rows are upserted into the tenant
     * table (existing updated, new created). Off-master rows are reported as
     * failures and skipped.
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
                $iso2 = mb_strtoupper(trim((string) ($row['data']['iso2'] ?? '')));

                if ($iso2 === '') {
                    $skipped++;

                    continue;
                }

                $lookup = Lookup::query()
                    ->where('type', Lookup::TYPE_COUNTRY)
                    ->where('iso2', $iso2)
                    ->first();

                if (! $lookup instanceof Lookup) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["Country '{$iso2}' is not in the master list."]];

                    continue;
                }

                $tenantAttributes = $this->tenantAttributesFromCsv($row['data']);

                /** @var Country|null $existing */
                $existing = Country::query()->where('lookup_id', $lookup->id)->first();

                if ($existing instanceof Country) {
                    $existing->fill($tenantAttributes)->save();
                    $updated++;
                } else {
                    Country::query()->create(
                        $this->snapshotAttributes($lookup) + $tenantAttributes + ['created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * Countries for CSV export (honours the same filters as the listing).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Country>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        /** @var Collection<int, Country> $countries */
        $countries = Country::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $countries;
    }

    /**
     * Snapshot the master entry's fields for a new tenant country row.
     *
     * @return array<string, mixed>
     */
    private function snapshotAttributes(Lookup $lookup): array
    {
        return [
            'lookup_id' => $lookup->id,
            'name' => $lookup->name,
            'iso2' => $lookup->iso2,
            'iso3' => $lookup->iso3,
            'numeric_code' => $lookup->numeric_code,
            'phone_code' => $lookup->phone_code,
            'capital' => $lookup->capital,
            'region' => $lookup->region,
            'currency_code' => $lookup->currency_code,
            'currency_symbol' => $lookup->currency_symbol,
            'locale' => $lookup->locale,
            'timezone' => $lookup->timezone,
            'flag_image' => $lookup->image,
        ];
    }

    /**
     * Extract the optional tenant-owned fields from a CSV row.
     *
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
     * Parse a CSV upload into header-keyed rows (with 1-based source row numbers).
     *
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

        // Normalise header names (and strip a UTF-8 BOM from the first cell).
        $header = array_map(static fn ($column): string => mb_strtolower(trim((string) $column)), $header);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];

        if (! in_array('iso2', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain an "iso2" column.']);
        }

        $rows = [];
        $rowNumber = 1; // header is row 1

        while (($cells = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($cells === [null]) {
                continue; // blank line
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
        Country::query()
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
