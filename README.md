# Rapnex Retail ERP

API-only, modular retail ERP built on **Laravel 12** / **PHP 8.4** / **PostgreSQL**, authenticated with **Laravel Passport** (OAuth2). The codebase is organized into self-contained modules via `nwidart/laravel-modules` (all sharing one database) and is designed to become multi-tenant.

> Backend coding standards are authoritative — see the *Rapid ERP Retail — Backend Coding Standards* document. Every endpoint returns the single JSON envelope `{status, code, message, data}`.

## Tech stack

- PHP 8.2+ (**8.4 recommended/target**)
- Laravel 12
- PostgreSQL
- Laravel Passport (OAuth2 bearer tokens)
- nwidart/laravel-modules (modular architecture)
- l5-swagger (OpenAPI / Swagger docs)
- Larastan (PHPStan) + Laravel Pint (static analysis & formatting)

## Modules

`Accounting`, `Admin`, `Inventory`, `Pos`, `Purchase`, `Sale` — each under `Modules/<Name>/`, all using the same database.

---

## Requirements

- PHP 8.2+ with extensions: `pdo_pgsql`, `mbstring`, `openssl`, `json`
- Composer 2.x
- PostgreSQL 13+

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your environment file and generate the app key
cp .env.example .env
php artisan key:generate
```

Then edit `.env` with your PostgreSQL connection:

```dotenv
APP_NAME=rapnex_retail_erp
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rapnex_retail_erp
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Optional: project-wide date-time format used by format_datetime()
DATETIME_FORMAT="d-F-Y H:i:s"
```

Create the database (`rapnex_retail_erp`) in PostgreSQL before migrating.

## Database (multi-tenant: 1 central DB + 1 DB per tenant)

This app is **database-per-tenant** via [`stancl/tenancy`](https://tenancyforlaravel.com/). There is one **central** database (tenants registry, the `users` identity directory, Passport `oauth_*`) and a **separate database per tenant** holding that tenant's roles/permissions and domain data.

```bash
# Central database (tenants, users, oauth_*)
php artisan migrate
```

Tenant databases are **created and migrated automatically** when a tenant is created (the `TenantCreated` job pipeline runs `CreateDatabase` + `MigrateDatabase`). Tenant-only migrations live in `database/migrations/tenant/`.

```bash
# Apply NEW tenant migrations to ALL existing tenant databases (data preserved)
php artisan tenants:migrate
```

> To **reset** tenant databases (drop & rebuild), or reset everything, see
> **Resetting the database** below.

> The central DB user must have permission to **CREATE DATABASE** (each tenant gets its own).
> If the `oauth_*` migrations are ever missing, publish them first:
> `php artisan vendor:publish --tag=passport-migrations`

### Resetting the database

There are two reset paths — pick by **what** you want to rebuild. Each step is ordered;
run them top to bottom.

#### A. Full reset — central + every tenant (most common)

Rebuilds the central DB (lookups, users, `oauth_*`) **and** re-provisions every tenant.
`migrate:fresh` only touches the central DB: it leaves the per-tenant databases behind
and empties `oauth_clients`. So the leftover tenant databases must be dropped **first** —
otherwise re-seeding collides on `tenant_001_database` and aborts with
`TenantDatabaseAlreadyExistsException`.

```bash
php artisan optimize:clear

# 1. Drop ALL physical tenant databases (incl. orphans no longer in the tenants
#    table). Without this, step 2 fails on the pre-existing tenant_001_database.
php artisan tenants:drop-databases --force

# 2. Rebuild central + seed. Seeding re-creates each tenant and provisions its
#    database automatically (CreateDatabase -> MigrateDatabase -> SeedDatabase),
#    so tenant schema + RBAC + the demo admins' role assignments all come back.
php artisan migrate:fresh --seed

# 3. Recreate the Passport personal-access client (migrate:fresh emptied oauth_clients;
#    without it, login fails — User::createToken has no client to issue against).
php artisan passport:client --personal --name="Rapnex Personal Access Client"
```

> On a brand-new machine with no tenant databases yet, step 1 is a harmless no-op.
> Step 2 re-seeds ~156k city lookups, so it takes ~40s.

#### B. Reset tenant databases only (keep central data + Passport)

Resets just the per-tenant schema/data — central lookups, users, and the Passport client
are left untouched. The existing tenant databases are **reused** (their tables are dropped
and re-migrated), so there is no orphan-database problem and no Passport step.

> ⚠️ `tenants:migrate-fresh` has **no `--seed` flag** — seed in a separate step (below).

```bash
# 1. Drop & re-migrate every tenant database's tables
php artisan tenants:migrate-fresh

# 2. Re-seed each tenant's roles, permissions, and column catalog
php artisan tenants:seed

# 3. Re-attach tenant admins to their roles. Step 1 also dropped the model_has_roles
#    pivot, and tenants:seed does NOT restore user->role links — only the central
#    TenantSeeder does. It's idempotent and won't recreate the existing databases.
php artisan db:seed --class="Database\Seeders\TenantSeeder" --force
```

### Backfilling RBAC onto existing tenants

When new authorizable resources/permissions are added (in `ResourceRegistry`), a full
re-seed isn't needed — backfill the **existing** tenant databases instead:

```bash
php artisan permissions:sync       # create/prune permission rows in every tenant DB
php artisan roles:sync-defaults    # re-apply the default roles' canonical grants (Tenant Admin gains new perms)
```

Run them in that order (sync the permissions, then grant them). Both accept `--tenant=<id>`
to target a single tenant.

## Passport setup (required)

Passport's encryption keys are environment-specific (git-ignored at `storage/*.key`) and the personal access client lives in the database, so **these must be run on every fresh setup / new machine**:

```bash
# 1. Generate the OAuth encryption keys (storage/oauth-private.key & oauth-public.key)
php artisan passport:keys

# 2. Create the personal access client used to issue API tokens (User::createToken)
php artisan passport:client --personal --name="Rapnex Personal Access Client"
```

> Shortcut: `php artisan passport:install` performs the equivalent steps in one go.
> Re-generate keys (overwriting existing) with `php artisan passport:keys --force`.

If you skip these, login returns a 500 (`relation "oauth_clients" does not exist`) or token issuance fails.

## Seed (platform admin + demo tenant)

```bash
php artisan db:seed
```

Idempotently creates (all password `password`):

- **Platform super-admin** (tenant-less, bypasses tenant scoping): `superadmin@rapnex.test`
- A **demo tenant** ("Rapnex Demo") — provisions its own database automatically
- A **tenant admin** for the demo tenant: `admin@demo.test`

## Run the app

```bash
php artisan serve
# API base URL: http://localhost:8000/api/v1
```

---

## One-shot setup (copy/paste)

```bash
composer install
cp .env.example .env
php artisan key:generate
# (edit .env DB credentials, then:)
php artisan migrate
php artisan passport:keys
php artisan passport:client --personal --name="Rapnex Personal Access Client"
php artisan db:seed
php artisan serve
```

---

## API documentation (Swagger)

Interactive API docs are generated from in-code OpenAPI annotations and served at:

- UI: **http://localhost:8000/api/documentation**

Regenerate the spec after changing endpoint annotations:

```bash
php artisan l5-swagger:generate
```

Authorize protected endpoints in the UI with a Passport token: log in, click **Authorize**, and enter `Bearer <access_token>`.

## Modules

```bash
# List modules
php artisan module:list

# Generate a new module (scaffolds API-only, standards-compliant stubs)
php artisan module:make <ModuleName>
```

Each module exposes its routes at `/api/v1/<module>` (`Modules/<Name>/routes/api.php`).

## Code quality

```bash
# Format (PSR-12) — run before committing
vendor/bin/pint

# Static analysis (Larastan / PHPStan, level 6)
php -d memory_limit=512M vendor/bin/phpstan analyse

# Tests
php artisan test
```

CI must pass `pint --test`, `phpstan`, and the test suite before merge.

## Conventions

- **Responses:** always the JSON envelope `{status, code, message, data}`; errors put detail in `data.errors` (list of strings). Built via the `ApiResponse` facade.
- **Dates:** all date/time output goes through the `format_datetime()` helper (format from `config('datetime.format')`) — never hardcode a format.
- **Auth:** Passport `auth:api` guard; API requests are forced to JSON (`ForceJsonResponse` middleware).
