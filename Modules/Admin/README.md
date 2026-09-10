# Admin Module

Platform-administration APIs — **tenant onboarding** (create tenants and their users). These are
central, cross-tenant operations performed by a **platform super-admin**, not by tenant users.

## Prerequisites for every request

- **Auth:** `Authorization: Bearer <access_token>` (from `POST /api/v1/auth/login`).
- **Platform admin only:** all routes run under `auth:api → platform.admin`. The caller must have
  `is_platform_admin = true`; any tenant user gets `403 Platform administrator access required.`
- **No tenant context:** these endpoints operate on the **central** `tenants` and `users` tables, so
  `tenant.init` is intentionally **not** applied.
- **Responses:** standard envelope `{status, code, message, data}`.

| Method | URI (`/api/v1/admin/...`) | Purpose |
|---|---|---|
| GET | `tenants` | List all tenants |
| POST | `tenants` | Create a tenant (auto-provisions its database) |
| POST | `tenants/{tenant}/users` | Create a user for a tenant and assign roles |

---

## Create a tenant — `POST /api/v1/admin/tenants`

Creating the tenant fires the `TenantCreated` job pipeline
(`CreateDatabase → MigrateDatabase → SeedDatabase`), so by the time the response returns the tenant's
database exists, is migrated, and is seeded with the default roles/permissions. **The request blocks for a
few seconds** while this runs.

Body: `name` (required, unique), `is_active` (optional, default `true`).

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"New Store","is_active":true}' \
  http://localhost:8000/api/v1/admin/tenants
```
```json
{
  "status": "success", "code": 200, "message": "Tenant created.",
  "data": { "id": "004", "name": "New Store", "is_active": true,
            "database": "tenant_004_database",
            "created_at": "10-June-2026 12:43:41", "updated_at": "10-June-2026 12:43:41" }
}
```
The tenant `id` is sequential and zero-padded (`004`); the database name is `tenant_<id>_database`.

## List tenants — `GET /api/v1/admin/tenants`
Returns every tenant with its id, name, active flag, and database name.

## Create a tenant user — `POST /api/v1/admin/tenants/{tenant}/users`

`{tenant}` is the tenant id (e.g. `004`); `404` if it doesn't exist. Creates a **central** user linked to the
tenant via `tenant_id`, then assigns roles **inside the tenant database**.

Body: `name`, `email` (required, globally unique), `password` (required, min 8), `phone` (optional),
`roles` (optional array of role **names**; defaults to `["Tenant Admin"]`). Role names are validated against
that tenant's roles — unknown roles return `422` and no user is created.

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Store Admin","email":"admin@newstore.test","password":"secret123"}' \
  http://localhost:8000/api/v1/admin/tenants/004/users
```
```json
{
  "status": "success", "code": 200, "message": "Tenant user created.",
  "data": { "user": { "id": 6, "name": "Store Admin", "email": "admin@newstore.test", "phone": null, "email_verified_at": null },
            "roles": ["Tenant Admin"] }
}
```

> Email is lower-cased and the name is title-cased automatically (User model mutators). The user then logs in
> via `POST /api/v1/auth/login`; subsequent requests resolve their tenant from `tenant_id`.

---

## Notes

- Tenant **deletion** isn't exposed here yet. Deleting a `Tenant` model drops its database
  (`TenantDeleted → DeleteDatabase`); add a guarded endpoint when needed.
- Interactive docs: `php artisan l5-swagger:generate`, browse `/api/documentation` (tag **Admin · Tenants**).
