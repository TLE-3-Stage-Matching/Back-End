# API Documentation (v1) – Front-end reference

**Base URL:** `https://<your-api-host>/api/v1`  
**Content type:** `application/json`  
**Auth:** JWT Bearer token for protected routes.

---

## Index

- [Authentication](#authentication)
  - [1. Register as coordinator](#1-register-as-coordinator-stage-coordinator)
  - [2. Login (get JWT)](#2-login-get-jwt)
  - [3. Current user (me)](#3-current-user-me)
  - [4. Logout](#4-logout)
  - [5. Refresh token](#5-refresh-token)
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

**Example:** `GET /coordinator/users?role=student&per_page=10`

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

---

## Recommended flow for coordinators

1. **Register** → `POST /auth/register/coordinator`
2. **Login** → `POST /auth/login` → store `token`
3. **Create company** → `POST /coordinator/companies` → store `data.id`
4. **Create company user** → `POST /coordinator/users` with `role: "company"` and `company_id: <id from step 3>`
5. **Create student** → `POST /coordinator/users` with `role: "student"` (no `company_id`)

Use the same Bearer token for all requests in steps 3–5.

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
