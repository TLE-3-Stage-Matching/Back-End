# API Documentation (v1) – Front-end reference

**Base URL:** `https://<your-api-host>/api/v1`  
**Content type:** `application/json`  
**Auth:** JWT Bearer token for protected routes.

---

## Index

- [Authentication](#authentication)
  - [1. Register as coordinator](#1-register-as-coordinator-stage-coordinator)
  - [2. Register as company (self-registration)(Team A)](#2-register-as-company-self-registration-team-a-only)
  - [3. Login (get JWT)](#3-login-get-jwt)
  - [4. Current user (me)](#4-current-user-me)
  - [5. Logout](#5-logout)
  - [6. Refresh token](#6-refresh-token)
- [Public data (no auth)](#public-data-no-auth)
  - [List active companies](#list-active-companies)
  - [List vacancies (active companies only)](#list-vacancies-active-companies-only)
- [Coordinator-only endpoints](#coordinator-only-endpoints)
- [Companies (coordinator)](#companies-coordinator)
  - [List companies](#list-companies)
  - [Create company](#create-company)
  - [Get company](#get-company)
  - [Update company](#update-company)
  - [Delete company](#delete-company)
- [Users (coordinator)](#users-coordinator)
  - [List users](#list-users)
  - [Create user (student or company)](#create-user-student-or-company)
  - [Get user](#get-user)
  - [Update user](#update-user)
  - [Delete user](#delete-user)
- [Recommended flow for coordinators](#recommended-flow-for-coordinators)
- [HTTP status codes](#http-status-codes)

---

## Authentication

### 1. Register as coordinator (stage coordinator)

Creates a new coordinator account. No auth required.

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/register/coordinator` |
| **Auth** | None |

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "email": "coordinator@example.com",
  "password": "yourpassword",
  "first_name": "Jan",
  "last_name": "Jansen"
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| email | string | Yes | Must be unique |
| password | string | Yes | Min 6 characters |
| first_name | string | Yes | |
| last_name | string | Yes | |

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

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

</details>

---

### 2. Register as company (self-registration) (Team A only)

Companies can register themselves. The company is created with `is_active: false` and does not appear in public company/vacancy listings until a **stage coordinator** approves it by setting `is_active` to `true` via [Update company](#update-company).

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/register/company` |
| **Auth** | None |

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "company": {
    "name": "Acme Corp",
    "industry_tag_id": null,
    "email": "info@acme.com",
    "phone": "+31201234567",
    "size_category": null,
    "photo_url": null
  },
  "location": {
    "city": "Amsterdam",
    "country": "Netherlands",
    "address_line": "Main Street 1",
    "postal_code": "1012 AB",
    "lat": 52.3676,
    "lon": 4.9041
  },
  "user": {
    "email": "contact@acme.com",
    "first_name": "Jan",
    "middle_name": null,
    "last_name": "Jansen",
    "phone": "+31612345678"
  },
  "password": "securepassword123",
  "password_confirmation": "securepassword123"
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| company.name | string | Yes | Max 255 |
| company.industry_tag_id | number | No | Must exist in `tags` |
| company.email | string | No | Email, max 255 |
| company.phone | string | No | Max 50 |
| company.size_category | string | No | Max 50 |
| company.photo_url | string | No | |
| location.city | string | Yes | |
| location.country | string | Yes | |
| location.address_line | string | No | |
| location.postal_code | string | No | Max 32 |
| location.lat | number | No | |
| location.lon | number | No | |
| user.email | string | Yes | Unique |
| user.first_name | string | Yes | Max 100 |
| user.middle_name | string | No | Max 100 |
| user.last_name | string | Yes | Max 100 |
| user.phone | string | No | Max 50 |
| password | string | Yes | Min 12, must be confirmed |

**Success (201):** Returns `data` (company, user, location) and `meta.token` / `meta.token_type` for immediate login.

---

### 3. Login (get JWT)

Returns a JWT for all subsequent protected requests.

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/login` |
| **Auth** | None |

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "email": "coordinator@example.com",
  "password": "yourpassword"
}
```

</details>

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer"
}
```

</details>

**Error (401):** `{ "message": "Invalid credentials" }`

**Usage:** Send the token on every protected request:
```http
Authorization: Bearer <token>
```

---

### 4. Current user (me)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/auth/me` |
| **Auth** | Bearer token required |

**Success (200):** `{ "data": <user object> }` (full user as returned by Laravel auth).

---

### 5. Logout

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/logout` |
| **Auth** | Bearer token required |

**Success (200):** `{ "message": "Logged out" }`

---

### 6. Refresh token

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/refresh` |
| **Auth** | Bearer token required |

**Success (200):** Same shape as login: `{ "token": "...", "token_type": "Bearer" }`

---

## Public data (no auth)

These endpoints return only **active** (coordinator-approved) companies and their vacancies. Use them for student/public frontends. Companies that registered via [Register as company](#2-register-as-company-self-registration-team-a-only) do not appear here until a stage coordinator sets their `is_active` to `true`.

### List active companies

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/companies` |
| **Auth** | None |

**Success (200):** `{ "data": [ <company objects> ], "links": { "self": "..." } }` — only companies with `is_active: true`.

---

### List vacancies (active companies only)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/vacancies` |
| **Auth** | None |

**Query parameters:** `per_page` (number, default 15) for pagination.

**Success (200):** `{ "data": [ <vacancy objects> ], "meta": { "current_page", "last_page", "per_page", "total" }, "links": { "self": "..." } }` — only vacancies belonging to active companies.

---

## Coordinator-only endpoints

All routes below require:

1. **Valid JWT** in `Authorization: Bearer <token>`.
2. **Logged-in user role = coordinator.**  
   Otherwise you get **403** (e.g. “Forbidden. Coordinator role required.” or “This action is unauthorized.”).

---

## Companies (coordinator)

Create and manage companies first; then add company users by `company_id`.

### List companies

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/companies` |

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

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
  "links": { "self": "..." }
}
```

</details>

---

### Create company

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/coordinator/companies` |

<details>
<summary><strong>Request body (JSON)</strong></summary>

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

</details>

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

<details>
<summary><strong>Request body (JSON)</strong></summary>

Same fields as [Create company](#create-company); all optional. Only send fields you want to change.

```json
{
  "is_active": true,
  "name": "Acme Corp",
  "email": "info@acme.com"
}
```

</details>

**Approving self-registered companies:** Set `is_active` to `true` to approve a company. Until then, that company and its users/vacancies are excluded from [List active companies](#list-active-companies) and [List vacancies](#list-vacancies-active-companies-only).

**Success (200):** `{ "data": <updated company> }`

---

### Delete company

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/coordinator/companies/{id}` |

**Success (204):** No content.

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
| role | string | — | `student` or `company` to filter |
| per_page | number | 15 | Pagination size |
| active_companies_only | boolean | false | If `1` or `true`, only return students and company users whose company is active (useful when listing users for display). Omit to see all users for management. |

**Example:** `GET /coordinator/users?role=student&per_page=10`  
**Example (only active companies’ users):** `GET /coordinator/users?active_companies_only=1`

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

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

</details>

---

### Create user (student or company)

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/coordinator/users` |

<details>
<summary><strong>Request body – Student (JSON)</strong></summary>

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

</details>

<details>
<summary><strong>Request body – Company user (JSON)</strong></summary>

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

</details>

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

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
  "message": "User created successfully.",
  "data": { <user object, same shape as list/show> }
}
```

</details>

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

<details>
<summary><strong>Request body (JSON)</strong></summary>

Only include fields to update.

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

</details>

**Success (200):** `{ "message": "User updated successfully.", "data": <user> }`

---

### Delete user

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/coordinator/users/{id}` |

**Success (200):** `{ "message": "User deleted successfully." }`  
**Error (404):** `{ "message": "User not found." }`

---

## Recommended flow for coordinators

1. **Register** → `POST /auth/register/coordinator`
2. **Login** → `POST /auth/login` → store `token`
3. **Create company** → `POST /coordinator/companies` → store `data.id`
4. **Create company user** → `POST /coordinator/users` with `role: "company"` and `company_id: <id from step 3>`
5. **Create student** → `POST /coordinator/users` with `role: "student"` (no `company_id`)

Use the same Bearer token for all requests in steps 3–5.

**Approving self-registered companies (Team A only):** Companies that registered via `POST /auth/register/company` start with `is_active: false`. To approve, use `PATCH /coordinator/companies/{id}` with `{ "is_active": true }`. Only active companies appear in `GET /companies` and `GET /vacancies`, and only their users when using `GET /coordinator/users?active_companies_only=1`.

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
