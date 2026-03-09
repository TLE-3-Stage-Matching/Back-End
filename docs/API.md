# API Documentation (v1) – Front-end reference

**Base URL:** `https://<your-api-host>/api/v1`  
**Content type:** `application/json`  
**Auth:** JWT Bearer token for protected routes.

For conventions on updating this doc and adding new API functionality, see [CONVENTIONS.md](CONVENTIONS.md).

[↑ Back to index](#index)

---

## Overview – by role

| Role | Capabilities |
|------|--------------|
| **Coordinator** | Register & login; full CRUD on **companies** and on **users** (students + company users); list **vacancies** (all companies) with filters; list companies/users with filters. |
| **Company user** | Login; view/update **own company**; view/update **own profile** (user + job title); full CRUD on **own company’s vacancies** (create/update can add tags by id or create new tags inline; new tags are saved to the DB). |
| **Any authenticated** | List **tags** (for vacancy forms); `GET /auth/me` (company users get `company_user` and `company` loaded). |

**Tags:** There is no standalone “create tag” endpoint. Tags are created **inline** when a company user creates or updates a vacancy by sending `{ "name": "...", "tag_type": "..." }` in the vacancy’s `tags` array; the backend uses `firstOrCreate` and persists new tags to the database.

[↑ Back to index](#index)

---

## Index

- [Overview – by role](#overview--by-role)
- [Authentication](#authentication)
  - [1. Register as coordinator](#1-register-as-coordinator-stage-coordinator)
  - [2. Login (get JWT)](#2-login-get-jwt)
  - [3. Current user (me)](#3-current-user-me)
  - [4. Logout](#4-logout)
  - [5. Refresh token](#5-refresh-token)
- **Company users**
  - [Company-only endpoints](#company-only-endpoints)
  - [My company](#my-company) — [Get](#get-my-company) · [Update](#update-my-company)
  - [My profile](#my-profile) — [Get](#get-my-profile) · [Update](#update-my-profile)
  - [Tags](#tags) — [List tags](#list-tags)
  - [Vacancies (company)](#vacancies-company) — [List](#list-company-vacancies) · [Create](#create-vacancy) · [Get](#get-vacancy) · [Update](#update-vacancy) · [Delete](#delete-vacancy)
- **Coordinators**
  - [Coordinator-only endpoints](#coordinator-only-endpoints)
  - [Companies (coordinator)](#companies-coordinator) — [List](#list-companies) · [Create](#create-company) · [Get](#get-company) · [Update](#update-company) · [Delete](#delete-company)
  - [Users (coordinator)](#users-coordinator) — [List](#list-users) · [Create](#create-user-student-or-company) · [Get](#get-user) · [Update](#update-user) · [Delete](#delete-user)
  - [Vacancies (coordinator)](#vacancies-coordinator) — [List](#list-vacancies-coordinator)
- **Reference**
  - [Recommended flow for coordinators](#recommended-flow-for-coordinators)
  - [Testing with Postman](#testing-with-postman)
  - [HTTP status codes](#http-status-codes)

[↑ Back to index](#index)

---

## Authentication

### 1. Register as coordinator (stage coordinator)

Creates a new coordinator account. No auth required.

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/register/coordinator` |
| **Auth** | None |

**Request body:**
```json
{
  "email": "coordinator@example.com",
  "password": "yourpassword",
  "first_name": "Jan",
  "last_name": "Jansen"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| email | string | Yes | Must be unique |
| password | string | Yes | Min 6 characters |
| first_name | string | Yes | |
| last_name | string | Yes | |

**Success (201):**
```json
{
  "message": "Coordinator account succesvol aangemaakt",
  "user": {
    "id": 1,
    "role": "coordinator",
    "email": "coordinator@example.com",
    "first_name": "Jan",
    "last_name": "Jansen"
  },
  "links": { "self": "..." }
}
```

---

### 2. Login (get JWT)

Returns a JWT for all subsequent protected requests.

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/login` |
| **Auth** | None |

**Request body:**
```json
{
  "email": "coordinator@example.com",
  "password": "yourpassword"
}
```

**Success (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer"
}
```

**Error (401):** `{ "message": "Invalid credentials" }`

**Usage:** Send the token on every protected request:
```http
Authorization: Bearer <token>
```

---

### 3. Current user (me)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/auth/me` |
| **Auth** | Bearer token required |

**Success (200):** `{ "data": <user object> }` (full user as returned by Laravel auth).

---

### 4. Logout

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/logout` |
| **Auth** | Bearer token required |

**Success (200):** `{ "message": "Logged out" }`

---

### 5. Refresh token

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/refresh` |
| **Auth** | Bearer token required |

**Success (200):** Same shape as login: `{ "token": "...", "token_type": "Bearer" }`

[↑ Back to index](#index)

---

## Company-only endpoints

All routes below require:

1. **Valid JWT** in `Authorization: Bearer <token>`.
2. **Logged-in user role = company** and a linked company.  
   Otherwise you get **403** (e.g. “Forbidden. Company role required.”).

[↑ Back to index](#index)

---

## My company

Company users can view and update **their own company** (the company they are linked to).

### Get my company

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/company` |
| **Auth** | Bearer token + company role |

Returns the authenticated user’s company.

**Success (200):** `{ "data": <company object>, "links": { "self": "..." } }`

---

### Update my company

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/company` |
| **Auth** | Bearer token + company role |

Update your company. Only include fields you want to change.

**Request body (all optional):**
```json
{
  "name": "Acme Corp",
  "industry_tag_id": 1,
  "email": "info@acme.com",
  "phone": "+31201234567",
  "size_category": "medium",
  "photo_url": null,
  "is_active": true
}
```

| Field | Type | Notes |
|-------|------|--------|
| name | string | Max 255 |
| industry_tag_id | number or null | Must exist in `tags` |
| email | string | Max 255 |
| phone | string | Max 50 |
| size_category | string | Max 50 |
| photo_url | string | |
| is_active | boolean | |

**Success (200):** `{ "data": <updated company>, "links": { "self": "..." } }`

[↑ Back to index](#index)

---

## My profile

Company users can view and update **their own profile** (user fields and job title).

### Get my profile

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/company/profile` |
| **Auth** | Bearer token + company role |

Returns the authenticated user’s profile including `company_user` and `company`.  
**Note:** `GET /auth/me` also returns the current user and, for company users, includes `company_user` and `company` when loaded.

**Success (200):**
```json
{
  "data": {
    "id": 1,
    "role": "company",
    "email": "hr@acme.com",
    "first_name": "Jane",
    "middle_name": null,
    "last_name": "Doe",
    "phone": null,
    "profile_photo_url": null,
    "created_at": "...",
    "updated_at": "...",
    "company_user": { "company_id": 1, "job_title": "HR Manager" },
    "company": { "id": 1, "name": "Acme Corp", ... }
  },
  "links": { "self": "..." }
}
```

---

### Update my profile

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/company/profile` |
| **Auth** | Bearer token + company role |

Update your user fields and/or job title. Only include fields you want to change. Omit `password` or send `null` to leave it unchanged.

**Request body (all optional):**
```json
{
  "first_name": "Jane",
  "middle_name": null,
  "last_name": "Doe",
  "phone": "+31612345678",
  "email": "jane.doe@acme.com",
  "password": null,
  "job_title": "HR Manager"
}
```

| Field | Type | Notes |
|-------|------|--------|
| first_name | string | Max 100 |
| middle_name | string or null | Max 100 |
| last_name | string | Max 100 |
| phone | string or null | Max 50 |
| email | string | Must be unique (excluding current user) |
| password | string or null | Min 6; omit or null to keep current |
| job_title | string or null | Max 255 |

**Success (200):** `{ "data": <updated profile>, "links": { "self": "..." } }`

[↑ Back to index](#index)

---

## Tags

Used when creating vacancies: company users can **select existing tags** (from this list) or **create new tags** inline in the vacancy payload.

### List tags

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/tags` |
| **Auth** | Bearer token required |

**Query parameters:**

| Param | Type | Description |
|-------|------|-------------|
| tag_type | string | Optional. Filter by tag type (e.g. `skill`, `industry`). |

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "PHP",
      "tag_type": "skill",
      "is_active": true,
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "links": { "self": "..." }
}
```

[↑ Back to index](#index)

---

## Vacancies (company)

Company users create and list vacancies for their own company. Each vacancy can have **tags**: either by **selecting existing tag IDs** (from `GET /tags`) or by **creating new tags** by sending `name` and `tag_type` in the payload.

### List company vacancies

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/company/vacancies` |
| **Auth** | Bearer token + company role |

Returns all vacancies for the authenticated user’s company.

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "location_id": null,
      "title": "Backend developer",
      "hours_per_week": 40,
      "description": "...",
      "offer_text": null,
      "expectations_text": null,
      "status": null,
      "created_at": "...",
      "updated_at": "...",
      "location": null,
      "vacancy_requirements": [
        {
          "vacancy_id": 1,
          "tag_id": 1,
          "requirement_type": "skill",
          "importance": null,
          "tag": { "id": 1, "name": "PHP", "tag_type": "skill" }
        }
      ]
    }
  ],
  "links": { "self": "..." }
}
```

---

### Create vacancy

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/company/vacancies` |
| **Auth** | Bearer token + company role |

**Request body:**
```json
{
  "title": "Backend developer",
  "location_id": null,
  "hours_per_week": 40,
  "description": "We are looking for...",
  "offer_text": null,
  "expectations_text": null,
  "status": "open",
  "tags": [
    { "id": 1 },
    { "name": "Laravel", "tag_type": "skill" },
    { "id": 2, "requirement_type": "skill", "importance": 1 }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| title | string | Yes | Max 255 |
| location_id | number | No | Must be a location of your company (`company_locations`) |
| hours_per_week | number | No | 1–168 |
| description | string | No | |
| offer_text | string | No | |
| expectations_text | string | No | |
| status | string | No | Max 32 |
| tags | array | No | List of tag references (see below) |

**Tags array** – each item is either:

- **Existing tag:** `{ "id": <tag_id> }`. Optional per item: `requirement_type` (string, max 16, default `"skill"`), `importance` (number).
- **New tag:** `{ "name": "<name>", "tag_type": "<tag_type>" }`. The tag is created if it doesn’t exist (matched by name + tag_type). Optional: `requirement_type`, `importance`.

**Success (201):** `{ "data": <vacancy with location and vacancy_requirements loaded>, "links": { "self": "..." } }`  
**Error (422):** Validation errors, or `"Location does not belong to your company."` if `location_id` is not one of your company’s locations.

---

### Get vacancy

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/company/vacancies/{id}` |
| **Auth** | Bearer token + company role |

Returns a single vacancy. The vacancy must belong to the authenticated user's company; otherwise **404** is returned.

**Success (200):** `{ "data": <vacancy with location and vacancy_requirements.tag>, "links": { "self": "...", "collection": "..." } }`  
**Error (404):** Vacancy not found or not owned by your company.

---

### Update vacancy

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/company/vacancies/{id}` |
| **Auth** | Bearer token + company role |

Update vacancy fields and/or replace its tags. Only include fields you want to change. To update tags, send a `tags` array (same format as [Create vacancy](#create-vacancy)); existing requirements are replaced. Omit `tags` to leave tags unchanged.

**Request body (all fields optional):**
```json
{
  "title": "Senior Backend developer",
  "location_id": 1,
  "hours_per_week": 36,
  "description": "Updated description...",
  "offer_text": null,
  "expectations_text": null,
  "status": "closed",
  "tags": [
    { "id": 1 },
    { "name": "Laravel", "tag_type": "skill" }
  ]
}
```

| Field | Type | Notes |
|-------|------|--------|
| title | string | Max 255 |
| location_id | number or null | Must be a location of your company, or null to clear |
| hours_per_week | number | 1–168 |
| description | string | |
| offer_text | string | |
| expectations_text | string | |
| status | string | Max 32 |
| tags | array | Same format as create; replaces all existing tags on the vacancy |

**Success (200):** `{ "data": <updated vacancy>, "links": { "self": "...", "collection": "..." } }`  
**Error (404):** Vacancy not found or not owned by your company.  
**Error (422):** Validation errors, or `"Location does not belong to your company."`

---

### Delete vacancy

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/company/vacancies/{id}` |
| **Auth** | Bearer token + company role |

Deletes the vacancy. The vacancy must belong to the authenticated user's company; otherwise **404** is returned.

**Success (204):** No content.  
**Error (404):** Vacancy not found or not owned by your company.

[↑ Back to index](#index)

---

## Coordinator-only endpoints

All routes below require:

1. **Valid JWT** in `Authorization: Bearer <token>`.
2. **Logged-in user role = coordinator.**  
   Otherwise you get **403** (e.g. “Forbidden. Coordinator role required.” or “This action is unauthorized.”).

[↑ Back to index](#index)

---

## Companies (coordinator)

Create and manage companies first; then add company users by `company_id`.

### List companies

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/companies` |

**Query parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| name | string | — | Filter by company name (partial match) |
| industry_tag_id | number | — | Filter by tag id |
| is_active | boolean | — | Filter by active status (`true`/`false`) |
| per_page | number | 15 | Pagination size |

**Example:** `GET /coordinator/companies?name=Acme&is_active=true&per_page=10`

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Acme Corp",
      "industry_tag_id": null,
      "email": "info@acme.com",
      "phone": null,
      "size_category": null,
      "photo_url": null,
      "is_active": true,
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": { "self": "..." }
}
```

---

### Create company

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/coordinator/companies` |

**Request body:**
```json
{
  "name": "Acme Corp",
  "industry_tag_id": null,
  "email": "info@acme.com",
  "phone": "+31201234567",
  "size_category": null,
  "photo_url": null,
  "is_active": true
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| name | string | Yes | Max 255 |
| industry_tag_id | number | No | Must exist in `tags` |
| email | string | No | Email, max 255 |
| phone | string | No | Max 50 |
| size_category | string | No | Max 50 |
| photo_url | string | No | |
| is_active | boolean | No | Defaults to `true` |

**Success (201):** `{ "data": <company object> }`  
Use `data.id` as `company_id` when creating a company user.

---

### Get company

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/companies/{id}` |

**Success (200):** `{ "data": <company>, "links": {...} }`  
**Error (404):** if company does not exist.

---

### Update company

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/coordinator/companies/{id}` |

**Request body:** Same fields as create; all optional. Only send fields you want to change.

**Success (200):** `{ "data": <updated company> }`

---

### Delete company

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/coordinator/companies/{id}` |

**Success (204):** No content.

[↑ Back to index](#index)

---

## Users (coordinator)

Manage **student** and **company** users. For company users, the company must exist (create it first via `/coordinator/companies`).

### List users

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/users` |

**Query parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| role | string | — | `student` or `company` to filter (e.g. students only: `role=student`) |
| search | string | — | Search in first name, last name, or email (partial match) |
| per_page | number | 15 | Pagination size |

**Example:** `GET /coordinator/users?role=student&search=jan&per_page=10`

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "role": "student",
      "email": "student@example.com",
      "first_name": "Test",
      "middle_name": null,
      "last_name": "Student",
      "phone": null,
      "created_at": "2025-03-09T12:00:00.000000Z",
      "updated_at": "2025-03-09T12:00:00.000000Z",
      "student_profile": { "user_id": 1 }
    },
    {
      "id": 2,
      "role": "company",
      "email": "hr@acme.com",
      "first_name": "Jane",
      "middle_name": null,
      "last_name": "Doe",
      "phone": null,
      "created_at": "...",
      "updated_at": "...",
      "company_user": { "company_id": 1, "job_title": "HR Manager" },
      "company": { "id": 1, "name": "Acme Corp" }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

---

### Create user (student or company)

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/coordinator/users` |

**Student – request body:**
```json
{
  "role": "student",
  "email": "student@example.com",
  "password": "secret123",
  "first_name": "Test",
  "middle_name": null,
  "last_name": "Student",
  "phone": null
}
```

**Company user – request body:**
```json
{
  "role": "company",
  "email": "hr@acme.com",
  "password": "secret123",
  "first_name": "Jane",
  "middle_name": null,
  "last_name": "Doe",
  "phone": null,
  "company_id": 1,
  "job_title": "HR Manager"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| role | string | Yes | `"student"` or `"company"` |
| email | string | Yes | Unique, max 255 |
| password | string | Yes | Min 6 |
| first_name | string | Yes | Max 100 |
| middle_name | string | No | Max 100 |
| last_name | string | Yes | Max 100 |
| phone | string | No | Max 50 |
| company_id | number | Yes if role=company | Must exist in `companies` |
| job_title | string | No | Max 255, for company only |

**Success (201):**
```json
{
  "message": "User created successfully.",
  "data": { <user object, same shape as list/show> }
}
```

**Errors:** 422 validation errors (e.g. duplicate email, missing `company_id` for company role).

---

### Get user

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/users/{id}` |

**Success (200):** `{ "data": <user object> }`  
**Error (404):** `{ "message": "User not found." }` (e.g. id is not a student/company user).

---

### Update user

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/coordinator/users/{id}` |

**Request body:** Only include fields to update.

```json
{
  "email": "new@example.com",
  "first_name": "Updated",
  "last_name": "Name",
  "password": "newpassword",
  "phone": "+31612345678"
}
```

For **company** users you can also send `company_id` and `job_title`.  
Omit `password` or send `null` to leave it unchanged.

**Success (200):** `{ "message": "User updated successfully.", "data": <user> }`

---

### Delete user

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/coordinator/users/{id}` |

**Success (200):** `{ "message": "User deleted successfully." }`
**Error (404):** `{ "message": "User not found." }`

[↑ Back to index](#index)

---

## Vacancies (coordinator)

Coordinators can list all vacancies across companies with optional filtering.

### List vacancies (coordinator)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/vacancies` |
| **Auth** | Bearer token + coordinator role |

**Query parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| company_id | number | — | Filter by company id |
| status | string | — | Filter by vacancy status |
| tag_id | number | — | Filter vacancies that have this tag in their requirements |
| search | string | — | Search in vacancy title (partial match) |
| per_page | number | 15 | Pagination size |

**Example:** `GET /coordinator/vacancies?company_id=1&status=open&per_page=10`

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "location_id": null,
      "title": "Backend developer",
      "hours_per_week": 40,
      "description": "...",
      "offer_text": null,
      "expectations_text": null,
      "status": "open",
      "created_at": "...",
      "updated_at": "...",
      "company": { "id": 1, "name": "Acme Corp", ... },
      "location": null,
      "vacancy_requirements": [
        {
          "vacancy_id": 1,
          "tag_id": 1,
          "requirement_type": "skill",
          "importance": null,
          "tag": { "id": 1, "name": "PHP", "tag_type": "skill" }
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": { "self": "..." }
}
```

[↑ Back to index](#index)

---

## Recommended flow for coordinators

1. **Register** → `POST /auth/register/coordinator`
2. **Login** → `POST /auth/login` → store `token`
3. **Create company** → `POST /coordinator/companies` → store `data.id`
4. **Create company user** → `POST /coordinator/users` with `role: "company"` and `company_id: <id from step 3>`
5. **Create student** → `POST /coordinator/users` with `role: "student"` (no `company_id`)

Use the same Bearer token for all requests in steps 3–5.

[↑ Back to index](#index)

---

## Testing with Postman

Use **Base URL** `http://localhost/api/v1` (or `http://127.0.0.1:8000/api/v1` if using `php artisan serve`). Set header **Content-Type:** `application/json` on all requests.
php -S 127.0.0.1:8001 -t public

### 1. Get a company user token

You need a JWT for a user with role **company** and a linked company.

**Option A – Existing company user**  
`POST /auth/login` with body:
```json
{ "email": "company-user@example.com", "password": "yourpassword" }
```
Copy `token` from the response.

**Option B – Create via coordinator**  
1. `POST /auth/register/coordinator` → register coordinator.  
2. `POST /auth/login` → login as coordinator, copy `token`.  
3. `POST /coordinator/companies` with **Authorization: Bearer &lt;token&gt;** → create company, note `data.id`.  
4. `POST /coordinator/users` with **Authorization: Bearer &lt;token&gt;** and body: `role: "company"`, `company_id` (id from step 3), email, password, first_name, last_name.  
5. `POST /auth/login` with that company user’s email/password → copy `token`.

### 2. Test tags and vacancies

Use **Authorization: Bearer** with the company user token for all requests below.

| Step | Method | Path | Notes |
|------|--------|------|--------|
| Get my company | GET | `/company` | Your company details |
| Update my company | PATCH | `/company` | Optional: name, email, phone, etc. |
| Get my profile | GET | `/company/profile` | User + company_user + company |
| Update my profile | PATCH | `/company/profile` | Optional: first_name, last_name, job_title, etc. |
| List tags | GET | `/tags` | Optional: `?tag_type=skill` |
| List vacancies | GET | `/company/vacancies` | Empty at first |
| Create vacancy | POST | `/company/vacancies` | See [Create vacancy](#create-vacancy) for body |
| Get vacancy | GET | `/company/vacancies/{id}` | Use `id` from create response |
| Update vacancy | PATCH | `/company/vacancies/{id}` | Optional fields + optional `tags` to replace |
| Delete vacancy | DELETE | `/company/vacancies/{id}` | Returns 204 |
| List vacancies again | GET | `/company/vacancies` | Should show created/updated vacancy or fewer after delete |

**Example create vacancy body** (existing tag by id):
```json
{
  "title": "Backend developer",
  "hours_per_week": 40,
  "description": "We are looking for...",
  "status": "open",
  "tags": [ { "id": 1 } ]
}
```

**Example with new tags** (creates tags if they don’t exist):
```json
{
  "title": "Frontend developer",
  "tags": [
    { "name": "JavaScript", "tag_type": "skill" },
    { "name": "React", "tag_type": "skill" }
  ]
}
```

**Common issues:** 403 = not a company user or no company linked. 422 = validation (e.g. missing `title`, or tag without `id` or without both `name` and `tag_type`).

[↑ Back to index](#index)

---

## HTTP status codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 204 | No content (e.g. delete company) |
| 401 | Unauthorized (missing or invalid token) |
| 403 | Forbidden (not a coordinator) |
| 404 | Not found |
| 422 | Validation error (body in response) |

[↑ Back to index](#index)
