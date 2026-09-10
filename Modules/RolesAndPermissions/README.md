# RolesAndPermissions Module

Tenant-scoped management APIs for **roles**, **permissions**, **user ↔ role assignment**, and the
per-role **column-visibility matrix**. This module is the HTTP surface only — the RBAC core
(models, `ResourceRegistry`, `ColumnVisibilityService`, policies) lives in the central `app/`.

## Prerequisites for every request

- **Auth:** send a Passport bearer token — `Authorization: Bearer <access_token>` (obtain via `POST /api/v1/auth/login`).
- **Tenant context:** all routes run under `auth:api → tenant.init → tenant.required`. The caller must be a
  **tenant user** (their `users.tenant_id` is set). A **platform admin** (no tenant) gets `403 These endpoints require a tenant context.` — cross-tenant management is not exposed here.
- **Permissions:** each endpoint requires a specific permission (table below). The **Tenant Admin** role holds all of them by default; **Manager**/**Staff** hold none, so they receive `403 This action is unauthorized.`
- **Responses:** always the standard envelope `{status, code, message, data}`. Validation errors return `422` with detail strings in `data.errors`.

| Method | URI (`/api/v1/...`) | Purpose | Permission |
|---|---|---|---|
| GET | `roles` | List roles + their permissions | `roles.view` |
| POST | `roles` | Create a role | `roles.create` |
| GET | `roles/{role}` | Show one role | `roles.view` |
| PUT | `roles/{role}` | Rename / set permissions | `roles.update` |
| DELETE | `roles/{role}` | Delete a role | `roles.delete` |
| PUT | `roles/{role}/permissions` | Replace a role's permission set | `roles.update` |
| GET | `permissions` | Read-only permission catalog | `permissions.view` |
| GET | `users/{user}/roles` | List a user's roles | `roles.view` |
| PUT | `users/{user}/roles` | Replace a user's roles | `roles.update` |
| GET | `column-catalog` | List catalogued columns | `columnVisibility.view` |
| GET | `roles/{role}/column-visibility` | Show a role's column matrix | `columnVisibility.view` |
| PUT | `roles/{role}/column-visibility` | Edit a role's column grants | `columnVisibility.update` |

---

## Roles

### List roles — `GET /api/v1/roles`
Returns every role in the tenant with its permission names and an `is_default` flag.

```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/roles
```
```json
{
  "status": "success", "code": 200, "message": "Roles retrieved.",
  "data": [
    { "id": 2, "name": "Manager", "guard_name": "api", "is_default": true,
      "permissions": ["users.create","users.view","users.viewFull","users.update"],
      "created_at": "10-June-2026 11:00:06", "updated_at": "10-June-2026 11:00:06" }
  ]
}
```

### Create a role — `POST /api/v1/roles`
`name` is required and unique (per `api` guard). `permissions` is optional; each must exist in the catalog
(`GET /api/v1/permissions`).

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Auditor","permissions":["users.view","users.viewFull"]}' \
  http://localhost:8000/api/v1/roles
```

### Show a role — `GET /api/v1/roles/{role}`
`{role}` is the role id. Returns the role with its permissions. `404` if not found.

### Update a role — `PUT /api/v1/roles/{role}`
Send `name` and/or `permissions` (both optional). Sending `permissions` **replaces** the whole set.
The three default roles (`Tenant Admin`, `Manager`, `Staff`) **cannot be renamed** (`403`) — but their
permissions may be tuned.

```bash
curl -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Senior Auditor","permissions":["users.view"]}' \
  http://localhost:8000/api/v1/roles/4
```

### Delete a role — `DELETE /api/v1/roles/{role}`
Removes the role and its assignments. Default roles **cannot be deleted** (`403`).

### Replace a role's permissions — `PUT /api/v1/roles/{role}/permissions`
Focused alternative to `PUT /roles/{role}` for just the permission set. `permissions` is required (send `[]` to clear).

```bash
curl -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"permissions":["users.view","users.create"]}' \
  http://localhost:8000/api/v1/roles/4/permissions
```

---

## Permissions

### List the permission catalog — `GET /api/v1/permissions`
Read-only. The available permissions (generated from `ResourceRegistry`), grouped by resource — use this to
populate role-permission pickers.

```json
{
  "status": "success", "code": 200, "message": "Permissions retrieved.",
  "data": [
    { "resource": "users", "permissions": ["users.create","users.view","users.viewFull","users.viewAny","users.update","users.delete","users.undoDelete"] },
    { "resource": "roles", "permissions": ["roles.view","roles.create","roles.update","roles.delete"] },
    { "resource": "permissions", "permissions": ["permissions.view"] },
    { "resource": "columnVisibility", "permissions": ["columnVisibility.view","columnVisibility.update"] }
  ]
}
```

---

## User ↔ role assignment

### List a user's roles — `GET /api/v1/users/{user}/roles`
`{user}` is the user id. The user must belong to the current tenant, otherwise `404`.

### Replace a user's roles — `PUT /api/v1/users/{user}/roles`
`roles` is required (array of role **names**; each must exist in the tenant). Sending `[]` removes all roles.

```bash
curl -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"roles":["Manager","Staff"]}' \
  http://localhost:8000/api/v1/users/5/roles
```

---

## Column-visibility matrix

Controls which columns each role can see. Each catalogued column has a **base** exposure
(`listing` > `detail` > `hidden`); a role's grant can only **raise** visibility above the base (raise-only).

### Column catalog — `GET /api/v1/column-catalog`
Lists the columns a grant can target, with their base exposure and sensitivity.

### Show a role's matrix — `GET /api/v1/roles/{role}/column-visibility`
Per column: `base_exposure`, the role's `granted_exposure` (or `null`), and the resulting `effective_exposure`.

### Edit a role's grants — `PUT /api/v1/roles/{role}/column-visibility`
`grants` is required: a list of `{resource, column, exposure}`. Rules:
- `exposure` is one of `listing`, `detail`, `hidden`.
- A grant **below** the column's base is rejected (`422`, raise-only).
- A grant **equal** to the base removes the grant (keeps the matrix minimal).

```bash
curl -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"grants":[{"resource":"users","column":"phone","exposure":"detail"}]}' \
  http://localhost:8000/api/v1/roles/3/column-visibility
```

---

## Extending RBAC (new models / new actions)

Permissions are **derived**, never hand-written. The single source of truth is
`app/Support/Authorization/ResourceRegistry.php` — its `resources()` array lists each authorizable
resource, and `permissions()` generates every name as `{resource}.{action}`.

### Add a new model/table

1. Register it in `ResourceRegistry::resources()` (add `use App\Models\Item;` at the top):
   ```php
   'items' => [
       'model' => Item::class,
       // omit 'actions'  -> all 7 actions (create, view, viewFull, viewAny, update, delete, undoDelete)
       // 'actions' => ['view', 'create', 'update', 'delete'],   // optional subset
       // 'columns' => [ 'name' => ['exposure' => ColumnExposure::Listing], ... ],  // optional column visibility
   ],
   ```
2. Sync + grant on **existing** tenants (new tenants get it automatically on provisioning):
   ```bash
   php artisan permissions:sync       # creates items.* in every tenant DB
   php artisan roles:sync-defaults    # grants them to Tenant Admin
   ```
   If you added a `columns` catalog, also run `php artisan tenants:seed` (seeds `resource_columns`).
3. **Enforce** it — add an `ItemPolicy` (map each action to `$actor->can('items.X')`) and call
   `$this->authorize(...)` in the controller. The registry only creates permission *names*; policies enforce them.

### Add `viewAny` / `undoDelete` to an existing resource (when it gains soft deletes)

The 7-action set already includes these — just widen the resource's `actions` (or drop the `actions` key to
inherit all 7):
```php
'roles' => [
    'model' => Role::class,
    'actions' => ['view', 'create', 'update', 'delete', 'viewAny', 'undoDelete'],
],
```
Then:
```bash
php artisan tenants:migrate        # only if you added a deleted_at column
php artisan permissions:sync       # creates the new permission rows
php artisan roles:sync-defaults    # grants them to Tenant Admin
```
Removing an action from the registry works the same way — `permissions:sync` prunes it from every tenant.

| Command | When |
|---|---|
| `permissions:sync` | Always, after editing the registry (creates **and prunes**) |
| `roles:sync-defaults` | Always, to grant new perms to the default roles |
| `tenants:seed` | Only if the resource has a `columns` catalog |
| `tenants:migrate` | Only if you added schema (e.g. `deleted_at`) |

---

## Notes

- **Default roles** `Tenant Admin` / `Manager` / `Staff` are protected from delete/rename and are seeded per tenant.
- After adding new resources/permissions in `ResourceRegistry`, backfill existing tenants:
  `php artisan permissions:sync` then `php artisan roles:sync-defaults` (both accept `--tenant=<id>`).
- Interactive docs: regenerate with `php artisan l5-swagger:generate`, browse at `/api/documentation` (tag **Roles & Permissions**).
