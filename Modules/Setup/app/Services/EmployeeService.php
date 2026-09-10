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
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\EmployeeDocument;

/**
 * Owns all reads/writes of the tenant `employees` table (+ the `employee_documents`
 * child rows, managed inline). Employees are tenant-defined custom records linked to
 * a tenant department + designation (required) and optionally country/state. Related
 * names are read through relations, never duplicated. Updates are gated by the
 * per-user column-visibility matrix.
 */
class EmployeeService
{
    private const RELATIONS = ['department', 'designation', 'country', 'state', 'documents'];

    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Employee>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;
        $designationId = isset($filters['designation_id']) ? (int) $filters['designation_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Employee::query()
            ->with(self::RELATIONS) // avoid N+1 when the resource reads related names + documents
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when($designationId !== null, fn ($query) => $query->where('designation_id', $designationId))
            ->when(
                $search !== null && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                    });
                },
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $id): Employee
    {
        /** @var Employee $employee */
        $employee = Employee::query()->with(self::RELATIONS)->findOrFail($id);

        return $employee;
    }

    /**
     * Create one tenant-defined employee, syncing any provided documents.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        $userId = Auth::guard('api')->id();
        $documents = Arr::pull($data, 'documents');

        return DB::transaction(function () use ($data, $documents, $userId): Employee {
            /** @var Employee $employee */
            $employee = Employee::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (is_array($documents)) {
                $this->syncDocuments($employee, $documents, $userId);
            }

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($employee->getKey());
            }

            return $employee->load(self::RELATIONS);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Employee>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('employees', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Employee> $updated */
            $updated = new Collection;

            foreach ($rows as $row) {
                $id = (string) $row['id'];
                $documents = Arr::pull($row, 'documents');
                $attributes = Arr::except($row, ['id', 'documents']);

                if ($writable !== null) {
                    $disallowed = array_diff(array_keys($attributes), $writable);

                    if ($disallowed !== []) {
                        throw ValidationException::withMessages([
                            'fields' => 'You are not permitted to edit: '.implode(', ', $disallowed).'.',
                        ]);
                    }
                }

                $employee = $this->find($id);
                $employee->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($employee->getKey());
                }

                $employee->save();

                if (is_array($documents)) {
                    $this->syncDocuments($employee, $documents, $employee->created_by);
                }

                $updated->push($employee->load(self::RELATIONS));
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
            /** @var Collection<int, Employee> $employees */
            $employees = Employee::query()->whereIn('id', $ids)->get();

            foreach ($employees as $employee) {
                $employee->delete();
            }

            return $employees->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Employee> $employees */
            $employees = Employee::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($employees as $employee) {
                // FK-less: remove the child documents explicitly on permanent delete.
                EmployeeDocument::withTrashed()->where('employee_id', $employee->getKey())->forceDelete();
                $employee->forceDelete();
            }

            return $employees->count();
        });
    }

    /**
     * Import employees from a CSV file (scalar fields only; documents excluded).
     * Rows are matched on `code` — found rows are updated, others created. Creating
     * a new employee requires name + department_id + designation_id + join_date.
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
                $code = trim((string) ($row['data']['code'] ?? ''));

                if ($code === '') {
                    $skipped++;

                    continue;
                }

                $attributes = $this->attributesFromCsv($row['data']);

                /** @var Employee|null $existing */
                $existing = Employee::query()->where('code', $code)->first();

                if ($existing instanceof Employee) {
                    $existing->fill($attributes)->save();
                    $updated++;

                    continue;
                }

                $missing = array_diff(['name', 'department_id', 'designation_id', 'join_date'], array_keys($attributes));

                if ($missing !== []) {
                    $failures[] = ['row' => $row['row'], 'errors' => ['Missing required fields to create employee: '.implode(', ', $missing).'.']];

                    continue;
                }

                Employee::query()->create(
                    $attributes + ['code' => $code, 'created_by' => $userId],
                );
                $imported++;
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Employee>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;
        $designationId = isset($filters['designation_id']) ? (int) $filters['designation_id'] : null;

        /** @var Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when($designationId !== null, fn ($query) => $query->where('designation_id', $designationId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $employees;
    }

    /**
     * The ordered scalar columns used by both CSV export and import (no documents).
     *
     * @return list<string>
     */
    public static function csvColumns(): array
    {
        return [
            'code', 'name', 'department_id', 'designation_id', 'email', 'phone', 'dob', 'gender', 'marital_status',
            'blood_group', 'religion', 'country_id', 'state_id', 'street', 'city', 'zip', 'national_id', 'iqama_no',
            'iqama_no_expiry_date', 'passport', 'passport_expiry_date', 'driving_license_no', 'driving_license_expiry_date',
            'tuv_no', 'tuv_no_expiry_date', 'join_date', 'type', 'employment_type', 'duty_type', 'bank_name',
            'bank_branch_name', 'bank_account_no', 'iban_num', 'company_name', 'basic_salary', 'transport_allowance',
            'gross_salary', 'hourly_rate', 'is_active', 'is_default', 'sort_order',
        ];
    }

    /**
     * Replace-sync the employee's documents: drop the existing set, recreate from
     * the payload. `file` is metadata only (no upload handling).
     *
     * @param  array<array-key, mixed>  $documents
     */
    private function syncDocuments(Employee $employee, array $documents, ?int $userId): void
    {
        EmployeeDocument::withTrashed()->where('employee_id', $employee->getKey())->forceDelete();

        foreach ($documents as $doc) {
            if (! is_array($doc) || trim((string) ($doc['name'] ?? '')) === '') {
                continue;
            }

            EmployeeDocument::query()->create([
                'employee_id' => $employee->getKey(),
                'name' => (string) $doc['name'],
                'file' => isset($doc['file']) ? (string) $doc['file'] : null,
                'expiry_date' => $doc['expiry_date'] ?? null,
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Map a CSV row to employee attributes (excludes code, the match key).
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function attributesFromCsv(array $data): array
    {
        $attributes = [];

        $strings = ['name', 'email', 'phone', 'gender', 'marital_status', 'blood_group', 'religion', 'street', 'city',
            'zip', 'national_id', 'iqama_no', 'passport', 'driving_license_no', 'tuv_no', 'type', 'employment_type',
            'duty_type', 'bank_name', 'bank_branch_name', 'bank_account_no', 'iban_num', 'company_name'];
        foreach ($strings as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        foreach (['department_id', 'designation_id', 'country_id', 'state_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = (int) $data[$field];
            }
        }

        foreach (['dob', 'iqama_no_expiry_date', 'passport_expiry_date', 'driving_license_expiry_date', 'tuv_no_expiry_date', 'join_date'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        foreach (['basic_salary', 'transport_allowance', 'gross_salary', 'hourly_rate'] as $field) {
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

        if (! in_array('code', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain a "code" column.']);
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
        Employee::query()
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
