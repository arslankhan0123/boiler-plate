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
use Modules\Setup\Models\Supplier;

/**
 * Owns all reads/writes of the tenant `suppliers` table. Suppliers are tenant-defined
 * custom records (NOT picked from the central lookups master). They may optionally
 * link to the tenant's own country/state/currency; those names are read through the
 * relations, never duplicated. Updates are gated by the per-user column-visibility
 * matrix.
 */
class SupplierService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Supplier>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $groupName = isset($filters['group_name']) ? (string) $filters['group_name'] : null;
        $countryId = isset($filters['country_id']) ? (int) $filters['country_id'] : null;
        $currencyId = isset($filters['currency_id']) ? (int) $filters['currency_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Supplier::query()
            ->with(['country', 'state', 'currency']) // avoid N+1 when the resource reads related names
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($groupName !== null && $groupName !== '', fn ($query) => $query->where('group_name', $groupName))
            ->when($countryId !== null, fn ($query) => $query->where('country_id', $countryId))
            ->when($currencyId !== null, fn ($query) => $query->where('currency_id', $currencyId))
            ->when(
                $search !== null && $search !== '',
                function ($query) use ($search): void {
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->whereRaw('LOWER(company_name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(phone) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(vat_number) LIKE ?', [$term]);
                    });
                },
            )
            ->orderBy('sort_order')
            ->orderBy('company_name')
            ->paginate($perPage);
    }

    public function find(string $id): Supplier
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::query()->with(['country', 'state', 'currency'])->findOrFail($id);

        return $supplier;
    }

    /**
     * Create one tenant-defined supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        $userId = Auth::guard('api')->id();

        return DB::transaction(function () use ($data, $userId): Supplier {
            /** @var Supplier $supplier */
            $supplier = Supplier::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($supplier->getKey());
            }

            return $supplier;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Supplier>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('suppliers', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Supplier> $updated */
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

                $supplier = $this->find($id);
                $supplier->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($supplier->getKey());
                }

                $supplier->save();
                $updated->push($supplier);
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
            /** @var Collection<int, Supplier> $suppliers */
            $suppliers = Supplier::query()->whereIn('id', $ids)->get();

            foreach ($suppliers as $supplier) {
                $supplier->delete();
            }

            return $suppliers->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Supplier> $suppliers */
            $suppliers = Supplier::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($suppliers as $supplier) {
                $supplier->forceDelete();
            }

            return $suppliers->count();
        });
    }

    /**
     * Import suppliers from a CSV file. Rows are matched on `company_name` — found
     * rows are updated, others created. Rows without a company_name are skipped.
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
                $companyName = trim((string) ($row['data']['company_name'] ?? ''));

                if ($companyName === '') {
                    $skipped++;

                    continue;
                }

                $attributes = $this->attributesFromCsv($row['data']);

                /** @var Supplier|null $existing */
                $existing = Supplier::query()->where('company_name', $companyName)->first();

                if ($existing instanceof Supplier) {
                    $existing->fill($attributes)->save();
                    $updated++;
                } else {
                    Supplier::query()->create(
                        $attributes + ['company_name' => $companyName, 'created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Supplier>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $countryId = isset($filters['country_id']) ? (int) $filters['country_id'] : null;
        $currencyId = isset($filters['currency_id']) ? (int) $filters['currency_id'] : null;

        /** @var Collection<int, Supplier> $suppliers */
        $suppliers = Supplier::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($countryId !== null, fn ($query) => $query->where('country_id', $countryId))
            ->when($currencyId !== null, fn ($query) => $query->where('currency_id', $currencyId))
            ->orderBy('sort_order')
            ->orderBy('company_name')
            ->get();

        return $suppliers;
    }

    /**
     * Map a CSV row to the writable supplier attributes (excludes company_name,
     * which the importer handles as the match key).
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function attributesFromCsv(array $data): array
    {
        $attributes = [];

        foreach (['vat_number', 'contact_person', 'email', 'phone', 'whatsapp', 'website', 'group_name', 'default_language', 'street', 'city', 'zip', 'po_box', 'mailing_address'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        foreach (['currency_id', 'country_id', 'state_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = (int) $data[$field];
            }
        }

        if (array_key_exists('opening_balance', $data) && $data['opening_balance'] !== '') {
            $attributes['opening_balance'] = (float) $data['opening_balance'];
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

        if (! in_array('company_name', $header, true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain a "company_name" column.']);
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
        Supplier::query()
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
