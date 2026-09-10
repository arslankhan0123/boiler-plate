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
use Modules\Setup\Models\Designation;

/**
 * Owns all reads/writes of the tenant `designations` table. Designations are
 * tenant-defined custom records, each linked to a tenant department (required).
 * The department name is read through the relation, never duplicated. Updates are
 * gated by the per-user column-visibility matrix.
 */
class DesignationService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Designation>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Designation::query()
            ->with('department') // avoid N+1 when the resource reads department_name
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when(
                $search !== null && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(code) LIKE ?', [$term]);
                    });
                },
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): Designation
    {
        /** @var Designation $designation */
        $designation = Designation::query()->with('department')->findOrFail($id);

        return $designation;
    }

    /**
     * Create one tenant-defined designation.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Designation
    {
        $userId = Auth::guard('api')->id();

        return DB::transaction(function () use ($data, $userId): Designation {
            /** @var Designation $designation */
            $designation = Designation::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($designation->getKey());
            }

            return $designation;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Designation>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('designations', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Designation> $updated */
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

                $designation = $this->find($id);
                $designation->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($designation->getKey());
                }

                $designation->save();
                $updated->push($designation);
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
            /** @var Collection<int, Designation> $designations */
            $designations = Designation::query()->whereIn('id', $ids)->get();

            foreach ($designations as $designation) {
                $designation->delete();
            }

            return $designations->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Designation> $designations */
            $designations = Designation::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($designations as $designation) {
                $designation->forceDelete();
            }

            return $designations->count();
        });
    }

    /**
     * Import designations from a CSV file. Rows are matched on `name` — found rows
     * are updated, others created. Creating a new designation requires a
     * `department_id` (rows without one are reported as failures).
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
                $name = trim((string) ($row['data']['name'] ?? ''));

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $attributes = $this->attributesFromCsv($row['data']);

                /** @var Designation|null $existing */
                $existing = Designation::query()->where('name', $name)->first();

                if ($existing instanceof Designation) {
                    $existing->fill($attributes)->save();
                    $updated++;

                    continue;
                }

                if (! array_key_exists('department_id', $attributes)) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["A department_id is required to create designation '{$name}'."]];

                    continue;
                }

                Designation::query()->create(
                    $attributes + ['name' => $name, 'created_by' => $userId],
                );
                $imported++;
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Designation>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;

        /** @var Collection<int, Designation> $designations */
        $designations = Designation::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $designations;
    }

    /**
     * Map a CSV row to the writable designation attributes (excludes name, the match key).
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function attributesFromCsv(array $data): array
    {
        $attributes = [];

        foreach (['code', 'description'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        if (array_key_exists('department_id', $data) && $data['department_id'] !== '') {
            $attributes['department_id'] = (int) $data['department_id'];
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

        if (! in_array('name', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain a "name" column.']);
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
        Designation::query()
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
