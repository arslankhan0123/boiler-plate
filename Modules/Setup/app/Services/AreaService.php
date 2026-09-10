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
use Modules\Setup\Models\Area;
use Modules\Setup\Models\City;

/**
 * Owns all reads/writes of the tenant `areas` table. Unlike the other Setup
 * resources, areas are NOT picked from the central lookups master — they are
 * tenant-defined custom records under a tenant city. Creating an area denormalises
 * its parent city/state/country names; updates are gated by the per-user
 * column-visibility matrix.
 */
class AreaService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Area>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $cityId = isset($filters['city_id']) ? (int) $filters['city_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Area::query()
            ->with('city.state.country') // avoid N+1 when the resource reads city/state/country names
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($cityId !== null, fn ($query) => $query->where('city_id', $cityId))
            ->when(
                $search !== null && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(address_line_1) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(area_code) LIKE ?', [$term]);
                    });
                },
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): Area
    {
        /** @var Area $area */
        $area = Area::query()->with('city.state.country')->findOrFail($id);

        return $area;
    }

    /**
     * Create one tenant-defined area under a city. The parent city's name and
     * ancestry (state/country) are denormalised onto the new row.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Area
    {
        $userId = Auth::guard('api')->id();

        // Ensure the parent city exists in this tenant before linking the area.
        City::query()->findOrFail($data['city_id']);

        return DB::transaction(function () use ($data, $userId): Area {
            /** @var Area $area */
            $area = Area::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($area->getKey());
            }

            return $area;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Area>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('areas', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Area> $updated */
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

                $area = $this->find($id);
                $area->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($area->getKey());
                }

                $area->save();
                $updated->push($area);
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
            /** @var Collection<int, Area> $areas */
            $areas = Area::query()->whereIn('id', $ids)->get();

            foreach ($areas as $area) {
                $area->delete();
            }

            return $areas->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Area> $areas */
            $areas = Area::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($areas as $area) {
                $area->forceDelete();
            }

            return $areas->count();
        });
    }

    /**
     * Import areas from a CSV file. Each row references its parent `city_id`; rows
     * are matched on (city_id, name) — found rows are updated, others created
     * (denormalising ancestry from the city). Rows whose `city_id` is not a tenant
     * city are reported as failures and skipped.
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
                $cityId = trim((string) ($row['data']['city_id'] ?? ''));
                $name = trim((string) ($row['data']['name'] ?? ''));

                if ($cityId === '' || $name === '') {
                    $skipped++;

                    continue;
                }

                /** @var City|null $city */
                $city = City::query()->find($cityId);

                if (! $city instanceof City) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["City [{$cityId}] is not in this tenant."]];

                    continue;
                }

                $attributes = $this->attributesFromCsv($row['data']);

                /** @var Area|null $existing */
                $existing = Area::query()
                    ->where('city_id', $city->id)
                    ->where('name', $name)
                    ->first();

                if ($existing instanceof Area) {
                    $existing->fill($attributes)->save();
                    $updated++;
                } else {
                    Area::query()->create(
                        $attributes + [
                            'city_id' => $city->id,
                            'name' => $name,
                            'created_by' => $userId,
                        ],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Area>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $cityId = isset($filters['city_id']) ? (int) $filters['city_id'] : null;

        /** @var Collection<int, Area> $areas */
        $areas = Area::query()
            ->with('city.state.country')
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($cityId !== null, fn ($query) => $query->where('city_id', $cityId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $areas;
    }

    /**
     * Map a CSV row to the writable area attributes (excludes city_id/name, which
     * the importer handles, and the denormalized ancestry).
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function attributesFromCsv(array $data): array
    {
        $attributes = [];

        foreach (['address_line_1', 'address_line_2', 'area_code', 'phone', 'email', 'description'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        foreach (['latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = (float) $data[$field];
            }
        }

        foreach (['is_active', 'is_default'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
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

        if (! in_array('city_id', $header, true) || ! in_array('name', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain "city_id" and "name" columns.']);
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
        Area::query()
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
