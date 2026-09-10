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
use Modules\Setup\Models\Currency;
use Modules\Setup\Models\Lookup;

/**
 * Owns all reads/writes of the tenant `currencies` table. Currencies are added by
 * picking entries from the central `lookups` master (type=currency,
 * restricted-to-master); the picked entry is snapshotted into the tenant row.
 * Identity fields are read-only; only tenant-owned columns may be updated, further
 * gated by the per-user column-visibility matrix.
 */
class CurrencyService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Currency>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Currency::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
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

    public function find(string $id): Currency
    {
        /** @var Currency $currency */
        $currency = Currency::query()->findOrFail($id);

        return $currency;
    }

    /**
     * Add currencies by picking master `lookup_id`s (type=currency). Each is
     * snapshotted into a new tenant row. Rejects ids absent from the master (wrong
     * type included) and ids already added; restores a soft-deleted match instead
     * of duplicating.
     *
     * @param  list<int>  $lookupIds
     * @return Collection<int, Currency>
     */
    public function createFromLookups(array $lookupIds): Collection
    {
        $userId = Auth::guard('api')->id();

        /** @var Collection<int, Lookup> $lookups */
        $lookups = Lookup::query()
            ->where('type', Lookup::TYPE_CURRENCY)
            ->whereIn('id', $lookupIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($lookupIds, $lookups, $userId): Collection {
            /** @var Collection<int, Currency> $created */
            $created = new Collection;

            foreach ($lookupIds as $lookupId) {
                $lookup = $lookups->get($lookupId);

                if (! $lookup instanceof Lookup) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "Currency [{$lookupId}] is not in the master list.",
                    ]);
                }

                if (Currency::query()->where('lookup_id', $lookupId)->exists()) {
                    throw ValidationException::withMessages([
                        'lookup_id' => "Currency [{$lookup->name}] has already been added.",
                    ]);
                }

                /** @var Currency|null $trashed */
                $trashed = Currency::onlyTrashed()->where('lookup_id', $lookupId)->latest('id')->first();

                if ($trashed instanceof Currency) {
                    $trashed->restore();
                    $created->push($trashed);

                    continue;
                }

                /** @var Currency $currency */
                $currency = Currency::query()->create(
                    $this->snapshotAttributes($lookup) + ['created_by' => $userId],
                );

                $created->push($currency);
            }

            return $created;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Currency>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('currencies', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Currency> $updated */
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

                $currency = $this->find($id);
                $currency->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($currency->getKey());
                }

                $currency->save();
                $updated->push($currency);
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
            /** @var Collection<int, Currency> $currencies */
            $currencies = Currency::query()->whereIn('id', $ids)->get();

            foreach ($currencies as $currency) {
                $currency->delete();
            }

            return $currencies->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Currency> $currencies */
            $currencies = Currency::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($currencies as $currency) {
                $currency->forceDelete();
            }

            return $currencies->count();
        });
    }

    /**
     * Import currencies from a CSV file. Rows are keyed by `code` and matched to the
     * master (type=currency); matched rows are upserted. Off-master rows are
     * reported as failures and skipped.
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
                $code = mb_strtoupper(trim((string) ($row['data']['code'] ?? '')));

                if ($code === '') {
                    $skipped++;

                    continue;
                }

                $lookup = Lookup::query()
                    ->where('type', Lookup::TYPE_CURRENCY)
                    ->where('code', $code)
                    ->first();

                if (! $lookup instanceof Lookup) {
                    $failures[] = ['row' => $row['row'], 'errors' => ["Currency '{$code}' is not in the master list."]];

                    continue;
                }

                $tenantAttributes = $this->tenantAttributesFromCsv($row['data']);

                /** @var Currency|null $existing */
                $existing = Currency::query()->where('lookup_id', $lookup->id)->first();

                if ($existing instanceof Currency) {
                    $existing->fill($tenantAttributes)->save();
                    $updated++;
                } else {
                    Currency::query()->create(
                        $this->snapshotAttributes($lookup) + $tenantAttributes + ['created_by' => $userId],
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'failures' => $failures];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Currency>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        /** @var Collection<int, Currency> $currencies */
        $currencies = Currency::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $currencies;
    }

    /**
     * Snapshot the master currency entry for a tenant row.
     *
     * @return array<string, mixed>
     */
    private function snapshotAttributes(Lookup $lookup): array
    {
        return [
            'lookup_id' => $lookup->id,
            'code' => $lookup->code,
            'name' => $lookup->name,
            'symbol' => $lookup->currency_symbol,
            'numeric_code' => $lookup->numeric_code,
            'decimal_digits' => $lookup->decimal_digits,
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
        Currency::query()
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
