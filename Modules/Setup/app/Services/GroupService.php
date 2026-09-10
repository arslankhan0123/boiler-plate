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
use Modules\Setup\Models\Group;

/**
 * Owns all reads/writes of the tenant `groups` table. Groups are tenant-defined
 * custom records forming a hierarchy via the self-referencing `parent_id`. Updates
 * are gated by the per-user column-visibility matrix; a group may not be made its
 * own parent.
 */
class GroupService
{
    public function __construct(private readonly ColumnVisibilityService $columns) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Group>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? (string) $filters['search'] : null;
        $parentId = isset($filters['parent_id']) ? (int) $filters['parent_id'] : null;
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;

        return Group::query()
            ->with('parent') // avoid N+1 when the resource reads parent_name
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($parentId !== null, fn ($query) => $query->where('parent_id', $parentId))
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

    public function find(string $id): Group
    {
        /** @var Group $group */
        $group = Group::query()->with('parent')->findOrFail($id);

        return $group;
    }

    /**
     * Create one tenant-defined group.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Group
    {
        $userId = Auth::guard('api')->id();

        return DB::transaction(function () use ($data, $userId): Group {
            /** @var Group $group */
            $group = Group::query()->create(
                Arr::except($data, ['created_by']) + ['created_by' => $userId],
            );

            if (($data['is_default'] ?? false) === true) {
                $this->clearOtherDefaults($group->getKey());
            }

            return $group;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, Group>
     */
    public function updateMany(array $rows): Collection
    {
        $writable = $this->columns->writableColumns('groups', $this->actingUser());

        return DB::transaction(function () use ($rows, $writable): Collection {
            /** @var Collection<int, Group> $updated */
            $updated = new Collection;

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $attributes = Arr::except($row, ['id']);

                if ($writable !== null) {
                    $disallowed = array_diff(array_keys($attributes), $writable);

                    if ($disallowed !== []) {
                        throw ValidationException::withMessages([
                            'fields' => 'You are not permitted to edit: '.implode(', ', $disallowed).'.',
                        ]);
                    }
                }

                if (array_key_exists('parent_id', $attributes) && (int) $attributes['parent_id'] === $id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'A group cannot be its own parent.',
                    ]);
                }

                $group = $this->find((string) $id);
                $group->fill($attributes);

                if (($attributes['is_default'] ?? false) === true) {
                    $this->clearOtherDefaults($group->getKey());
                }

                $group->save();
                $updated->push($group);
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
            /** @var Collection<int, Group> $groups */
            $groups = Group::query()->whereIn('id', $ids)->get();

            foreach ($groups as $group) {
                $group->delete();
            }

            return $groups->count();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            /** @var Collection<int, Group> $groups */
            $groups = Group::onlyTrashed()->whereIn('id', $ids)->get();

            foreach ($groups as $group) {
                $group->forceDelete();
            }

            return $groups->count();
        });
    }

    /**
     * Import groups from a CSV file. Rows are matched on `name` — found rows are
     * updated, others created. Rows without a name are skipped.
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

                /** @var Group|null $existing */
                $existing = Group::query()->where('name', $name)->first();

                if ($existing instanceof Group) {
                    $existing->fill($attributes)->save();
                    $updated++;
                } else {
                    Group::query()->create(
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
     * @return Collection<int, Group>
     */
    public function forExport(array $filters): Collection
    {
        $isActive = array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null;
        $parentId = isset($filters['parent_id']) ? (int) $filters['parent_id'] : null;

        /** @var Collection<int, Group> $groups */
        $groups = Group::query()
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when($parentId !== null, fn ($query) => $query->where('parent_id', $parentId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $groups;
    }

    /**
     * Map a CSV row to the writable group attributes (excludes name, the match key).
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function attributesFromCsv(array $data): array
    {
        $attributes = [];

        foreach (['code', 'description', 'color'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== '') {
            $attributes['parent_id'] = (int) $data['parent_id'];
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
        Group::query()
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
