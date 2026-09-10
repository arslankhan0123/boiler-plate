# Inventory Module Plan — Categories, Items & Inventory Listings (per-tenant)

> Status: PLAN (not yet implemented). Scope chosen: **full inventory roadmap**.
> "Inventory listings" = **stock balance per item per store**.
> Target module: `Modules/Inventory`. All domain tables live in the **per-tenant
> database** (one DB per tenant via stancl/tenancy), never the central DB.

---

## 0. Context & findings

- The reference project (`rapid-erp-retail`) has **no inventory implementation** to
  port — its `Modules/Inventory` is a bare scaffold. The only inventory artifacts
  there are a *roadmap comment* (`items, item_groups, units, brands, sizes, colors,
  stores [Store→Rack→Bin→Sub-Bin], opening_stock, physical_stock, stock_transfers,
  wastage, assets, stock_reports`, barcodes via picqer, CSV import via
  maatwebsite/excel) and matching permission names in its Admin seeder. So this is a
  **fresh build**, designed to *this* project's (stricter) conventions.
- This project's Inventory module (`Modules/Inventory`) is likewise a scaffold:
  `InventoryController` stub + a single `GET /api/v1/inventory` route. The route
  group currently has only `auth:api` and **no `tenant.init`** — that must change.
- Conventions are already established and MUST be followed (see
  `MEMORY.md` → coding-standards): API-only single JSON envelope via
  `App\Facades\ApiResponse`; thin controllers; Form Requests for validation;
  Services for logic; Resources in `Transformers/`; `declare(strict_types=1)`;
  Larastan level 6 + Pint green; OpenAPI attrs on every endpoint.

---

## 1. Non-negotiables (the "must be in tenant DB, separate per tenant" requirement)

1. **Every inventory table is a TENANT-DB table.** Migrations go in
   `database/migrations/tenant/` (root path), **NOT** in
   `Modules/Inventory/database/migrations/` — the latter is auto-discovered as a
   *central* migration and would create the tables in the central DB. The tenant
   path is what `config/tenancy.php → migration_parameters` (`--path =>
   database_path('migrations/tenant')`) and the `MigrateDatabase` provisioning job
   run. (We keep them in the root tenant path even though they belong conceptually
   to the Inventory module; document this in the module README.)

2. **Models resolve to the tenant connection.** Add a tiny trait
   `Modules\Inventory\Models\Concerns\UsesTenantConnection` that overrides
   `getConnectionName(): ?string => Config::string('database.default')` — identical
   to `App\Models\Role`/`Permission`. This guards against connection inheritance
   when a tenant model is loaded as a relation of a central-pinned model. Apply it
   to **all** inventory models.

3. **No cross-database foreign keys to the central `users` table.** Inventory tables
   live in the tenant DB; `users` lives in the central DB. Columns like `created_by`
   must be a plain `unsignedBigInteger('created_by')->nullable()` **with no
   `constrained()`** — a tenant→central FK is impossible. (FKs *between* inventory
   tables and to tenant-DB tables like `stores`/`roles` are fine.)

6. **Every soft-deleted table also carries `deleted_by_user_id`** (project-wide
   standard). Any table here with `softDeletes()` — `categories`, `brands`, `stores`,
   `items`, `assets`, and any other soft-deleted master — gets an
   `unsignedBigInteger('deleted_by_user_id')->nullable()` (FK-less, central user id —
   same rule as `created_by`). It is stamped with the acting user on soft delete and
   cleared on restore via a shared `App\Models\Concerns\TracksSoftDeleteUser` trait
   (overrides `runSoftDelete()` + a `restoring` hook); bulk soft-deletes set it in the
   same `DB::transaction()`. Append-only / balance tables (`stock_movements`,
   `inventory_listings`) have no soft delete, so they do not get this column.

4. **RBAC + column visibility are per-tenant** and already wired — we extend them,
   we don't reinvent. New resources are registered in
   `App\Support\Authorization\ResourceRegistry`; permissions/roles/column-catalog are
   then materialised in each tenant DB by `TenantDatabaseSeeder` (fresh tenants) and
   the `permissions:sync` / `roles:sync-defaults` commands (existing tenants).

5. **Routes carry `auth:api` + `tenant.init` + `permission:` middleware.** Without
   `tenant.init` the queries hit the central DB. Permission names are
   `{resource}.{action}` (dot form, guard `api`), e.g. `permission:items.view`.

---

## 2. Domain model (tenant DB)

Resource keys use this project's camelCase convention (matching `columnVisibility`);
URIs are kebab-case; route names are `api.inventory.{resource}.{action}`.

### 2.1 Master / lookup data

| Resource key   | Table              | Purpose / key columns |
|----------------|--------------------|------------------------|
| `categories`   | `categories`       | Hierarchical product classification (**this is the reference roadmap's `item_groups`, unified**). `name`, `code?`, `parent_id?` (self-FK, `nullOnDelete`), `is_active`, `sort_order`, soft deletes. |
| `units`        | `units`            | Units of measure. `name`, `abbreviation` (unique), `is_active`. |
| `brands`       | `brands`           | `name` (unique), `code?`, `is_active`, soft deletes. |
| `sizes`        | `sizes`            | `name` (unique), `sort_order`, `is_active`. |
| `colors`       | `colors`           | `name` (unique), `hex_code?`, `is_active`. |
| `stores`       | `stores`           | Physical store/location (top of hierarchy). `name`, `code` (unique), `address?`, `is_default`, `is_active`, soft deletes. |
| `storeLocations` | `store_locations` | Rack→Bin→Sub-Bin within a store. `store_id` (FK), `parent_id?` (self-FK), `type` (enum: `rack`/`bin`/`sub_bin`), `code`, unique `(store_id, code)`. |

> **Design note — `categories` vs `item_groups`:** the reference lists both implicitly;
> they overlap. This plan **unifies them into a single hierarchical `categories`
> table** (cleaner, avoids two near-identical classifiers). If you want them split
> (e.g. `category` for catalog browsing + `item_group` for tax/reporting), say so and
> I'll add a flat `item_groups` table + `items.item_group_id`.

### 2.2 Catalog

| Resource key  | Table           | Purpose / key columns |
|---------------|-----------------|------------------------|
| `items`       | `items`         | Product master. `name`, `sku` (unique, auto-generated), `barcode?` (unique, auto), `category_id` (FK), `brand_id?` (FK), `unit_id` (FK), `cost_price` decimal(15,2), `selling_price` decimal(15,2), `tax_rate` decimal(5,2), `track_stock` bool, `reorder_level` decimal(15,4), `is_active`, `created_by?`, soft deletes. |
| `itemVariants`| `item_variants` | Per-`size`×`color` variant of an item (the roadmap's "variants/size/color"). `item_id` (FK, cascade), `size_id?` (FK), `color_id?` (FK), `sku` (unique), `barcode?` (unique), `selling_price?` (override), unique `(item_id, size_id, color_id)`. |

### 2.3 Stock — "inventory listings" + movement ledger

| Resource key        | Table                 | Purpose / key columns |
|---------------------|-----------------------|------------------------|
| `inventoryListings` | `inventory_listings`  | **The user's "inventory listings": current stock balance per item(-variant) per store.** `item_id` (FK), `item_variant_id?` (FK), `store_id` (FK), `on_hand` decimal(15,4), `reserved` decimal(15,4) default 0, `available` (computed = on_hand − reserved, exposed in API not stored, or stored & maintained), `reorder_level?`, unique `(item_id, item_variant_id, store_id)`. **No soft delete** (it's a balance). |
| `stockMovements`    | `stock_movements`     | Immutable audit ledger; every quantity change writes one row. `item_id`, `item_variant_id?`, `store_id`, `type` (enum, see §4), `quantity` signed decimal(15,4), `balance_after` decimal(15,4), `reference_type?`+`reference_id?` (polymorphic to the source doc), `note?`, `created_by?`, `created_at`. **Append-only — no update/delete, no soft delete.** |

### 2.4 Stock operations (each posts to the ledger + updates listings)

| Resource key     | Table(s)                                   | Purpose |
|------------------|--------------------------------------------|---------|
| `openingStock`   | `opening_stocks` (+ lines)                 | Initial balances per item/store at go-live. Posts `opening` movements. |
| `physicalStock`  | `physical_stock_counts` (+ lines)          | Stock-take sessions; the difference vs system on_hand posts `adjustment` movements. |
| `stockTransfers` | `stock_transfers` + `stock_transfer_lines` | Move stock store→store; posts `transfer_out` (from) + `transfer_in` (to). Has a status workflow (`draft`/`in_transit`/`received`). |
| `wastage`        | `wastages` (+ lines)                       | Recorded losses/shrinkage; posts `wastage` (negative) movements. |
| `assets`         | `assets`                                   | Fixed-asset register (inventory-as-asset). Standalone CRUD; `name`, `code`, `category_id?`, `purchase_cost`, `purchased_at`, `status`. |

### 2.5 Reporting (no table)

| Resource key   | Backing                | Purpose |
|----------------|------------------------|---------|
| `stockReports` | queries over the above | Read-only: current-stock report, movement report; CSV/Excel export. `actions => ['view']` only. |

### 2.6 Relationship summary

```
categories ──< categories (parent_id)        stores ──< store_locations ──< (self parent_id)
categories ──< items >── brands               stores ──< inventory_listings
units      ──< items                          stores ──< stock_movements
items      ──< item_variants >── sizes,colors
items / item_variants / stores  ──<  inventory_listings   (unique triple)
items / item_variants / stores  ──<  stock_movements      (append-only ledger)
stock_transfers ──< stock_transfer_lines      (and opening/physical/wastage + their lines)
```

---

## 3. Phased delivery

Each phase is independently shippable, green (Pint + PHPStan + tests), and ends with
the **registration + sync** step (§5). Recommended migration timestamp order matches
this sequence (FK targets must migrate first).

- **Phase 1 — Master data:** `categories`, `units`, `brands`, `sizes`, `colors`.
  Full CRUD each. Establishes the lookups items depend on.
- **Phase 2 — Stores & locations:** `stores`, `store_locations`.
- **Phase 3 — Catalog:** `items` (+ SKU/barcode generation, pricing/cost/tax,
  filtering/search/pagination), then `item_variants`.
- **Phase 4 — Inventory listings & ledger:** `inventory_listings` +
  `stock_movements` + the `StockService` engine (§4). Read endpoints for listings;
  `openingStock` is the first writer.
- **Phase 5 — Stock operations:** `opening_stocks`, `physical_stock_counts`,
  `stock_transfers`, `wastages` — all routed through `StockService` inside
  `DB::transaction()`.
- **Phase 6 — Assets register:** `assets` CRUD.
- **Phase 7 — Reports & export:** `stockReports` (current stock + movement),
  CSV/Excel via `maatwebsite/excel`.
- **Phase 8 — Barcodes/labels & import:** barcode/label image rendering (picqer
  `picqer/php-barcode-generator`), items CSV/Excel import.

> If you'd rather ship the **minimum useful slice first**, Phases 1–4 (masters →
> stores → items/variants → per-store stock listings) deliver exactly the
> "categories, items, inventory listings" you originally named; 5–8 are the rest of
> the roadmap.

---

## 4. Stock engine (the correctness-critical part)

A single `Modules\Inventory\Services\StockService` owns **all** balance mutations so
the ledger and the denormalised balance never drift:

```php
public function post(
    StockMovementType $type,   // backed enum
    int $itemId,
    ?int $itemVariantId,
    int $storeId,
    string $quantity,          // signed decimal as string; never float
    ?Model $reference = null,  // source doc (transfer line, count, …) for the morph
    ?string $note = null,
): StockMovement
```

Rules:
- Runs inside `DB::transaction()`; **`lockForUpdate()`** on the
  `inventory_listings` row (or `firstOrCreate` then lock) to prevent races.
- Writes one immutable `stock_movements` row with `balance_after`, then updates
  `inventory_listings.on_hand`.
- `available` = `on_hand − reserved` (computed accessor on the listing; not a stored
  duplicate unless we later need to query/sort by it).
- Quantities and money use **decimal casts**, never float (`decimal:4` for qty,
  `decimal:2` for money) per coding standards.

`StockMovementType` (backed enum, `app` of the Inventory module):
`opening`, `adjustment`, `transfer_in`, `transfer_out`, `wastage`,
`sale_out`, `purchase_in`, `return_in`, `return_out`. (Sales/Purchase modules will
call `StockService::post()` later via the module's public service — no cross-module
internal imports.)

SKU/barcode generation lives in a `CodeGeneratorService` (prefix + zero-padded
sequence for SKU; EAN-13/Code128 value for barcode). Phase 8 renders the barcode
image/label via picqer.

---

## 5. RBAC, column visibility & per-tenant sync (every phase)

For each new resource:

1. **Register in `App\Support\Authorization\ResourceRegistry::resources()`** with its
   `model`, a `columns` visibility catalog, and (optionally) an `actions` subset.
   Example:
   ```php
   'items' => [
       'model' => \Modules\Inventory\Models\Item::class,
       'columns' => [
           'name'          => ['exposure' => ColumnExposure::Listing],
           'sku'           => ['exposure' => ColumnExposure::Listing],
           'selling_price' => ['exposure' => ColumnExposure::Listing],
           'cost_price'    => ['exposure' => ColumnExposure::Hidden, 'sensitive' => true], // margin-sensitive
           'tax_rate'      => ['exposure' => ColumnExposure::Detail],
       ],
   ],
   'stockReports' => [ 'model' => /* a marker */, 'actions' => ['view'] ],
   ```
   This auto-creates the 7 permissions (`items.create`, `items.view`, …) — Tenant
   Admin gains them automatically (it holds `ResourceRegistry::permissions()`).
2. **Grant Manager/Staff** what they should have in
   `DefaultRoles::permissionsFor()` (and any column raises in `columnGrantsFor()` —
   e.g. let Manager see `items.cost_price` at Detail).
3. **Transformer** uses the `FiltersVisibleColumns` trait, implements
   `visibilityResource()` (returns the resource key), wraps output in
   `filterVisibleColumns(...)`, and dates go through `format_datetime()`.
4. **Controller** sets `column_context` (`ColumnExposure::Listing` on `index`,
   `Detail` on `show`) before returning the resource.
5. **Materialise in tenant DBs:**
   - Fresh tenants: handled automatically by `TenantDatabaseSeeder`.
   - New schema in existing tenants: `php artisan tenants:migrate`.
   - New permissions in existing tenants: `php artisan permissions:sync`.
   - Backfill default-role grants/columns: `php artisan roles:sync-defaults`.

This naturally gives a strong column-visibility story: **Staff see `selling_price`
but not `cost_price`/margin**; Manager/Tenant Admin get cost via an additive grant.

---

## 6. Routing

Replace the body of `Modules/Inventory/routes/api.php` so the whole group is
tenant-initialised and each action is permission-gated. URIs kebab-case, route names
`api.inventory.*` (no `v1` in the name):

```php
Route::prefix('v1')
    ->middleware(['auth:api', 'tenant.init'])     // tenant.init is the critical addition
    ->group(function (): void {
        Route::prefix('inventory')->name('inventory.')->group(function (): void {
            Route::apiResource('categories', CategoryController::class)
                ->middlewareFor(['index','show'], 'permission:categories.view')
                ->middlewareFor('store',  'permission:categories.create')
                ->middlewareFor('update', 'permission:categories.update')
                ->middlewareFor('destroy','permission:categories.delete');
            // …items, item-variants, stores, store-locations, inventory-listings,
            //   opening-stock, physical-stock, stock-transfers, wastage, assets…
            Route::get('stock-reports/current', [StockReportController::class, 'current'])
                ->middleware('permission:stockReports.view')->name('stock-reports.current');
        });
    });
```

(`inventory-listings` and `stock-reports` are read-only → `->only(['index','show'])`
/ explicit GETs.) Record-level rules, where needed, go in Policies mapping the 7
actions — never inline `authorize()` in controllers.

---

## 7. Per-resource build recipe (checklist)

For each resource, in order:

1. **Migration** in `database/migrations/tenant/` — anonymous class, `up()` + `down()`,
   FKs with on-delete + indexes, unique constraints; `created_by` is FK-less; **if the
   table has `softDeletes()`, also add FK-less `deleted_by_user_id` (§1.6).**
2. **Model** in `Modules/Inventory/app/Models/` — `UsesTenantConnection` trait,
   explicit `$fillable`, `casts()` (decimals/bools/enums/dates), typed relations,
   `SoftDeletes` + `TracksSoftDeleteUser` on masters, factory.
3. **Form Requests** `Store…Request` / `Update…Request` in `Http/Requests/`
   (validation only; `Rule::unique` etc.).
4. **Transformer** in `Transformers/` with `FiltersVisibleColumns` + `format_datetime`.
5. **Service** in `Services/` for any non-trivial logic (stock posting, SKU/barcode,
   transfer workflow); multi-write ops in `DB::transaction()`.
6. **Controller** in `Http/Controllers/` — thin, `: JsonResponse` via `ApiResponse`,
   sets `column_context`, OpenAPI `#[OA\*]` attrs on every action.
7. **Routes** + `permission:` middleware (§6).
8. **Register** in `ResourceRegistry` + `DefaultRoles` (§5).
9. **Tests** — feature tests for happy / 422 / 401 / 403, asserting on the envelope;
   factories; cover the stock-balance math for movements.
10. **Sync** — `tenants:migrate`, `permissions:sync`, `roles:sync-defaults`; OpenAPI
    regenerate (`l5-swagger:generate`); Pint + PHPStan green.

---

## 8. New packages (Phases 7–8 only)

- `maatwebsite/excel` — CSV/Excel import & export (items import, stock report export).
- `picqer/php-barcode-generator` — barcode/label image rendering.

(Phases 1–6 need no new packages.)

---

## 9. Open decisions (flag before/early in build)

1. **`categories` vs `item_groups`** — unified into one hierarchical `categories`
   table here (§2.1). Split them? (default: keep unified)
2. **Variant model** — `inventory_listings`/`stock_movements` key on
   `item_id` + **nullable** `item_variant_id` (handles both variant and non-variant
   items). Alternative: force every item to have a default variant. (default:
   nullable variant_id)
3. **`reserved` stock** — included on listings for future sales-order allocation, but
   nothing writes it until the Sales/POS modules integrate. Keep the column now?
   (default: yes, future-proof)
4. **`store_locations` (Rack→Bin→Sub-Bin)** — included for roadmap completeness but
   adds depth. Stock is tracked at **store** granularity, not bin, unless you want
   bin-level balances (heavier). (default: store-level stock; locations are an
   organisational tree only)

---

## 10. Commands cheat-sheet (after changes)

```bash
php artisan tenants:migrate          # apply new tenant tables to existing tenants
php artisan permissions:sync         # create new permissions in every tenant DB
php artisan roles:sync-defaults      # backfill default-role grants + column matrix
php artisan l5-swagger:generate      # refresh API docs
php vendor/bin/pint --test           # PSR-12
php -d memory_limit=512M vendor/bin/phpstan analyse   # Larastan level 6
```
