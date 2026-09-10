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
use Modules\Setup\Models\Department;

/**
 * Owns all reads/writes of the tenant `departments` table. Departments are
 * tenant-defined custom records (a flat list, NOT picked from any master). Updates
 * are gated by the per-user column-visibility matrix.
 */
class DepartmentService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Department>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Department::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
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

    public function find(string $id): Department
    {
        /** @var Department $department */
        $department = Department::query()->findOrFail($id);

        return $department;
    }

    /**
     * Create one tenant-defined department.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        $userId = Auth::guard('api')->id();

        return DB::transaction(function () use ($data, $userId): Department {
            /** @var Department $department */
            $department = Department::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($department->getKey());
            }

            return $department;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Department>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('departments', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Department> $updated */
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

                $department = $this->find($id);
                $department->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($department->getKey());
                }

                $department->save();
                $updated->push($department);
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
            /** @var Collection<int, Department> $departments */
            $departments = Department::query()->whereIn('id', $ids)->get();

            foreach ($departments as $department) {
                $department->delete();
            }

            return $departments->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Department> $departments */
            $departments = Department::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($departments as $department) {
                $department->forceDelete();
            }

            return $departments->count();
        });
    }

    /**
     * Import departments from a CSV file. Rows are matched on `name` — found rows
     * are updated, others created. Rows without a name are skipped.
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

        DB::transaction(function () use ($rows, $userId, &$imported, &$updated, &$skipped): void {
            foreach ($rows as $row) {
                $name = trim((string) ($row['data']['name'] ?? ''));

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $attributes = $this->attributesFromCsv($row['data']);

                /** @var Department|null $existing */
                $existing = Department::query()->where('name', $name)->first();

                if ($existing instanceof Department) {
                    $existing->fill($attributes)->save();
                    $updated++;
                } else {
                    Department::query()->create(
                        $attributes + ['name' => $name, 'created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Department>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        /** @var Collection<int, Department> $departments */
        $departments = Department::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $departments;
    }

    /**
     * Map a CSV row to the writable department attributes (excludes name, which the
     * importer handles as the match key).
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
        Department::query()
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
