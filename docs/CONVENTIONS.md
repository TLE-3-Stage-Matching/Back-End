# Conventions for AI and maintainers

This file describes how to **update the API documentation** (`docs/API.md`) and how to **add new functionality** to the backend so changes stay consistent with the existing codebase.

---

## Part 1: Updating the documentation (`docs/API.md`)

### Structure

- **Top:** Base URL, content type, auth note. Then **Overview – by role** (short table of what each role can do).
- **Index:** Table of contents with anchor links. It is grouped by:
  - **Company users** (company-only, my company, my profile, tags, vacancies)
  - **Coordinators** (coordinator-only, companies, users, vacancies)
  - **Reference** (recommended flow, testing, HTTP status codes)
- **Sections:** Each major section is a `##` heading. Subsections use `###` (e.g. "Get vacancy", "Create vacancy").

### When adding or changing an endpoint

1. **Add or update the section** for that endpoint using the existing pattern:
   - A **Method / Path / Auth** table.
   - **Query parameters** table if the endpoint supports filters (Param, Type, Default, Description).
   - **Request body** with a JSON example and a **Field / Type / Required / Notes** table.
   - **Success** and **Error** response descriptions (with status codes and response shape).
2. **Add a “Back to index” link** at the **end** of every major `##` section (before the next `---` and `##`):
   ```markdown
   [↑ Back to index](#index)
   ---
   ## Next section
   ```
   Also add this link at the very end of the document (after the last section).
3. **Update the Index** in `docs/API.md`:
   - Add a new bullet (and sub-bullets if needed) under the right group (**Company users**, **Coordinators**, or **Reference**).
   - Use the same link text and anchor as the section heading (e.g. `[List things](#list-things)`). Anchors are usually lowercase, with spaces and punctuation replaced by hyphens.
4. **Update “Overview – by role”** if the new endpoint changes what a role can do. Edit the table and/or the short paragraph under it (e.g. tags, inline creation).
5. **Optional:** If the endpoint is useful for the Postman flow, add a row to the **Testing with Postman** table (Method, Path, short note).

### Endpoint doc format (copy-paste template)

```markdown
### Action name

| | |
|---|---|
| **Method** | `GET` or `POST` or `PUT` or `PATCH` or `DELETE` |
| **Path** | `/path/of/endpoint` or `/path/{id}` |
| **Auth** | Bearer token + role if required |

Optional: **Query parameters:** table (Param, Type, Default, Description).

Optional: **Request body:** JSON example, then Field/Type/Required/Notes table.

**Success (200 or 201):** Short description and/or example shape.
**Error (4xx):** When it happens and response shape.
```

### Style

- Paths: use backticks, e.g. `/company/vacancies`.
- Response payloads: `{ "data": ... }` or `{ "data": <description> }` when the shape is obvious.
- Keep examples minimal but valid (e.g. real field names, plausible types).

---

## Part 2: Adding new functionality

### Routes (`routes/api.php`)

- All API routes live under the `v1` prefix.
- **Public** routes (e.g. login, register coordinator) are inside `Route::prefix('v1')->group(...)` but **outside** `auth:api`.
- **Protected** routes are inside `Route::middleware('auth:api')->group(...)`.
- **Role-scoped** routes use middleware:
  - `company` → company user with a linked company (e.g. `company`, `company/profile`, `company/vacancies`).
  - `coordinator` → coordinator only (e.g. `coordinator/companies`, `coordinator/users`, `coordinator/vacancies`).
- Use **kebab-case** for path segments (e.g. `company/vacancies`, not `companyVacancies`).
- For update, support both `PUT` and `PATCH`: `Route::match(['put', 'patch'], 'path/{model}', [...]).
- Register **list/index** routes before **resource** routes that use `{id}` or `{model}` so the list path is not interpreted as an id.

### Controllers

- **Location:** Use namespaces by audience/feature:
  - `App\Http\Controllers` for non-API (e.g. `CompanyController`, `AuthController`).
  - `App\Http\Controllers\Api` for API (e.g. `TagController`).
  - `App\Http\Controllers\Api\Company` for company-user-only (e.g. `VacancyController`, `CompanyAccountController`).
  - `App\Http\Controllers\Api\Coordinator` for coordinator-only (e.g. `CoordinatorVacancyController`).
- **Return type:** Use `JsonResponse` for JSON responses and `Response` (Symfony) for no-content (e.g. 204).
- **Response shape:** Prefer a consistent envelope:
  - Success: `['data' => $resource]` and optionally `'links' => ['self' => url(...), 'collection' => url(...)]`.
  - Paginated: `'data' => $items`, `'meta' => ['current_page', 'last_page', 'per_page', 'total']`, and optionally `'links' => ['self' => ...]`.
- **Authorization:** For scoped resources (e.g. “own company”, “own vacancy”), resolve the resource and check ownership (e.g. `$vacancy->company_id === $request->user()->companyUser->company->id`). Return **404** when the user is not allowed (do not leak existence with 403 if that is the project’s choice).
- **Eager load** relations that the response includes to avoid N+1 (e.g. `->with(['company', 'vacancyRequirements.tag'])`).

### Form requests

- **Location:** `App\Http\Requests` or `App\Http\Requests\Api` (and `App\Http\Requests\Coordinator` for coordinator-only).
- **Naming:** `StoreXxxRequest`, `UpdateXxxRequest` for create/update.
- **authorize():** Return `true` only if the user has the right role/relationship (e.g. `(bool) $this->user()?->companyUser` for company endpoints).
- **rules():** Use Laravel validation rules; use `sometimes` for PATCH (optional fields). For unique fields (e.g. email), use `Rule::unique('table','column')->ignore($currentId)`.
- **Docblock:** Use `@return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>` for `rules()` where appropriate.

### Filtering (list endpoints)

- **Query parameters:** Read with `$request->filled('param')` or `$request->has('param')`; only add conditions when the param is present.
- **Exact match:** `$query->where('column', $request->input('param'))`.
- **Partial match:** `$query->where('column', 'like', '%' . $request->input('param') . '%')`.
- **Related table:** `$query->whereHas('relation', fn ($q) => $q->where(...))`.
- **Boolean:** Use `$request->has('param')` so `?param=0` is valid; normalize with `filter_var($request->input('param'), FILTER_VALIDATE_BOOLEAN)` if needed.
- **Pagination:** `$query->paginate($request->integer('per_page', 15))` and return `data` plus `meta` (current_page, last_page, per_page, total).

### Middleware

- **New role middleware:** Create in `App\Http\Middleware`, register in `bootstrap/app.php` under `$middleware->alias([...])`.
- **Behaviour:** Check `$request->user()` and role (e.g. `UserRole::Company`). For company, also ensure `$user->companyUser` exists. Return 403 JSON with a short message if unauthorized.

### Database / models

- Use **Eloquent** and existing patterns (fillable, casts, relationships). For create-or-get by attributes, use `Model::firstOrCreate($attributes, $defaults)` so new rows are persisted.

### Summary checklist when adding a new endpoint

1. Add route(s) in `routes/api.php` under the correct middleware (auth, company, or coordinator).
2. Create or reuse a controller; keep response shape consistent (`data`, optional `links`, optional `meta`).
3. Use a Form Request for create/update with `authorize()` and `rules()`.
4. Enforce ownership or scope in the controller when applicable (404 if not allowed).
5. Eager load relations used in the response.
6. Update `docs/API.md`: new or updated section, Back to index link, Index entry, and Overview – by role if the role’s capabilities change.
7. If it’s a list endpoint with filters, document query parameters and use the filtering conventions above.

---

## Quick reference

| Item | Convention |
|------|------------|
| API prefix | `v1` |
| JSON envelope | `{ "data": ..., "links": {...}, "meta": {...} }` |
| Pagination | `per_page` (default 15), meta: current_page, last_page, per_page, total |
| Update method | Support both PUT and PATCH |
| Auth | JWT via `auth:api`; role via `company` or `coordinator` middleware |
| 403 | Wrong role or missing link (e.g. no company) |
| 404 | Resource missing or not owned by the user (when scoped) |
| 422 | Validation error or business rule (e.g. “Location does not belong to your company”) |
| Doc back link | `[↑ Back to index](#index)` at end of each major section and at end of file |
| Index groups | Company users → Coordinators → Reference |
