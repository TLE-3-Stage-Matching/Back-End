# API Documentation (v1) – Front-end reference

**Base URL:** `https://<your-api-host>/api/v1`  
**Content type:** `application/json`  
**Auth:** JWT Bearer token for protected routes.

For conventions on updating this doc and adding new API functionality, see [CONVENTIONS.md](CONVENTIONS.md) (For Backend only).

[↑ Back to index](#index)

---

## Overview – by role

| Role | Capabilities |
|------|--------------|
| **Coordinator** | Register & login; full CRUD on **companies** and on **users** (students + company users); list **vacancies** (all companies) with filters; list companies/users with filters. |
| **Company user** | Login; view/update **own company**; view/update **own profile** (user + job title); full CRUD on **own company's vacancies** (create/update can add tags by id or create new tags inline; new tags are saved to the DB). |
| **Student** | Login; view/update **own profile** (user + student_profile); CRUD **experiences**; manage **preferences**, **languages**, and **tags/skills**. |
| **Any authenticated** | List **tags** (for vacancy forms); `GET /auth/me` (company users get `company_user` and `company` loaded). |

**Tags:** There is no standalone “create tag” endpoint. Tags are created **inline** when a company user creates or updates a vacancy by sending `{ "name": "...", "tag_type": "..." }` in the vacancy’s `tags` array; the backend uses `firstOrCreate` and persists new tags to the database.

[↑ Back to index](#index)

---

## Index

- [Overview – by role](#overview--by-role)
- [Authentication](#authentication)
  - [1. Register as coordinator](#1-register-as-coordinator-stage-coordinator)
  - [2. Register as company (self-registration)](#2-register-as-company-self-registration)
  - [3. Login (get JWT)](#3-login-get-jwt)
  - [4. Current user (me)](#4-current-user-me)
  - [5. Logout](#5-logout)
  - [6. Refresh token](#6-refresh-token)
  - [7. Using JWT from front-ends (SPA / mobile)](#7-using-jwt-from-front-ends-spa--mobile)
- [Company users](#company-users)
  - [Company-only endpoints](#company-only-endpoints)
  - [My company](#my-company)
  - [My profile](#my-profile)
  - [Tags](#tags)
  - [Vacancies (company)](#vacancies-company)
- [Students](#students)
  - [Student-only endpoints](#student-only-endpoints)
  - [Student profile](#student-profile)
  - [Student preferences](#student-preferences)
  - [Student experiences](#student-experiences)
  - [Student languages](#student-languages)
  - [Student tags](#student-tags)
- [Coordinators](#coordinators)
  - [Coordinator-only endpoints](#coordinator-only-endpoints)
  - [Companies (coordinator)](#companies-coordinator)
  - [Users (coordinator)](#users-coordinator)
  - [Vacancies (coordinator)](#vacancies-coordinator)
- [Public data (no auth)](#public-data-no-auth)
  - [List active companies](#list-active-companies)
  - [List vacancies (active companies only)](#list-vacancies-active-companies-only)
- [Reference](#reference)
  - [Recommended flow for coordinators](#recommended-flow-for-coordinators)
  - [Testing with Postman](#testing-with-postman)
  - [HTTP status codes](#http-status-codes)

[↑ Back to top](#api-documentation-v1--front-end-reference)

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

[↑ Back to index](#index)

---

### 7. Using JWT from front-ends (SPA / mobile)

This section explains **how to implement authentication in a front-end** (React, Vue, Angular, mobile, etc.), **how to send the Bearer token**, how **refresh** works, and how to **prevent deep-linking into protected pages**.

#### 7.1 Basic login flow

1. **Show a login form** that posts to `POST /api/v1/auth/login` with `email` and `password`.
2. On **success**, the API returns:
   ```json
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     "token_type": "Bearer"
   }
   ```
3. **Store the token** somewhere accessible to your HTTP client:
   - For SPAs, a common (simple) option is `localStorage` (e.g. `localStorage.setItem('token', token)`).
   - For higher security you can keep it **in memory only** (Redux/Pinia/Zustand/etc.) and re-login on full refresh. This avoids XSS-based token theft but requires a new login when the user reloads the page.
4. After login, **navigate to your app’s protected area** (e.g. `/dashboard`) and start using the token on all protected calls.

#### 7.2 Sending the Bearer token

For every protected API request, include the token as a **Bearer** token in the `Authorization` header:

```http
Authorization: Bearer <token>
```

**Example with `fetch` (vanilla JS):**

```js
const token = localStorage.getItem('token');

const res = await fetch('/api/v1/company', {
  headers: {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  },
});
```

#### 7.3 How refresh works

- JWTs are **time-limited**. When a token expires, protected endpoints will start returning **401 Unauthorized**.
- To keep the user logged in without showing the login screen again, you can call `POST /api/v1/auth/refresh` with the **current (still-present) token** in the `Authorization` header.
- The response has the same shape as login:
  ```json
  {
    "token": "new-token-here",
    "token_type": "Bearer"
  }
  ```
- After a successful refresh:
  - **Replace** the old token in your storage (localStorage / memory) with the new value.
  - All subsequent requests should use the new token.

**Typical pattern in front-ends:**

1. Use a central HTTP client (e.g. a small wrapper around `fetch`).
2. On **401 responses**, try to:
   - Call `/auth/refresh` once.
   - If refresh succeeds, update the stored token, retry the original request, and continue.
   - If refresh fails (401 again), **log the user out** on the client (clear token + redirect to login).

**Example with a small `fetch` wrapper (simplified pseudo-code):**

```js
async function apiRequest(path, options = {}) {
  const token = localStorage.getItem('token');

  const res = await fetch(`/api/v1${path}`, {
    ...options,
    headers: {
      ...(options.headers || {}),
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  });

  // If token expired, try refresh once
  if (res.status === 401 && token) {
    const refreshRes = await fetch('/api/v1/auth/refresh', {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });

    if (!refreshRes.ok) {
      localStorage.removeItem('token');
      window.location.href = '/login';
      throw new Error('Session expired');
    }

    const refreshData = await refreshRes.json();
    const newToken = refreshData.token;
    localStorage.setItem('token', newToken);

    // Retry original request with new token
    const retryRes = await fetch(`/api/v1${path}`, {
      ...options,
      headers: {
        ...(options.headers || {}),
        'Content-Type': 'application/json',
        Authorization: `Bearer ${newToken}`,
      },
    });

    return retryRes;
  }

  return res;
}
```

#### 7.4 Preventing deep-linking into protected routes

“Deep-linking” here means typing a protected URL directly into the browser (e.g. `/app/company`) or refreshing on it while **not authenticated**. To prevent this, your front-end should:

1. **Track auth state** (e.g. `isAuthenticated`, plus the current token).
2. **Protect routes** with guards that:
   - Check if there is a token.
   - Optionally call `/auth/me` once on app startup to validate the token and load the user.
   - Redirect to `/login` when there is no valid token.

**Example (React Router-like pseudo-code):**

```jsx
function PrivateRoute({ children }) {
  const token = localStorage.getItem('token');

  if (!token) {
    // Not logged in → send to login
    return <Navigate to="/login" replace />;
  }

  return children;
}

// Usage:
// <Route path="/app/company" element={<PrivateRoute><CompanyPage /></PrivateRoute>} />
```

On **initial app load**, a common pattern is:

1. Read the token from storage.
2. If present, call `/auth/me` (with `Authorization: Bearer <token>`) to:
   - Confirm it is still valid.
   - Get the current user (role, company, student profile, etc.).
3. If `/auth/me` fails with 401, clear the token and send the user to `/login`.

This ensures that:

- Direct links to protected pages **only work** when there is a valid token.
- When the token is missing or invalid, users are **always redirected to the login page**, even if they try to deep-link or refresh on a protected route.

[↑ Back to index](#index)

---

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
  "banner_url": null,
  "description": null,
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
| banner_url | string | Max 512 |
| description | string | |
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

## Student-only endpoints

All routes below require:

1. **Valid JWT** in `Authorization: Bearer <token>`.
2. **Logged-in user role = student.**  
   Otherwise you get **403** (e.g. "Forbidden. Student role required.").

[↑ Back to index](#index)

---

## Student profile

Students can view and update their own profile (user fields + student_profile).

### Get student profile

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/profile` |
| **Auth** | Bearer token + student role |

Returns the authenticated student's full profile including experiences, tags, languages, and preferences.

**Success (200):**
```json
{
  "data": {
    "id": 1,
    "role": "student",
    "email": "student@example.com",
    "first_name": "John",
    "middle_name": null,
    "last_name": "Doe",
    "phone": "+31612345678",
    "profile_photo_url": null,
    "created_at": "...",
    "updated_at": "...",
    "student_profile": {
      "headline": "Junior Developer",
      "bio": "Passionate about coding...",
      "address_line": "Main Street 1",
      "postal_code": "1234AB",
      "city": "Amsterdam",
      "country": "Netherlands",
      "searching_status": "active",
      "exclude_demographics": false,
      "exclude_location": false
    },
    "student_experiences": [...],
    "student_tags": [...],
    "student_languages": [...],
    "student_preferences": {...}
  },
  "links": { "self": "..." }
}
```

---

### Update student profile

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/student/profile` |
| **Auth** | Bearer token + student role |

Update user fields and/or student profile fields. Only include fields you want to change.

**Request body (all optional):**
```json
{
  "first_name": "John",
  "middle_name": null,
  "last_name": "Doe",
  "phone": "+31612345678",
  "email": "john.doe@example.com",
  "password": "newpassword",
  "headline": "Junior Full-Stack Developer",
  "bio": "Passionate about building web applications...",
  "address_line": "Main Street 1",
  "postal_code": "1234AB",
  "city": "Amsterdam",
  "country": "Netherlands",
  "searching_status": "active",
  "exclude_demographics": false,
  "exclude_location": false
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
| headline | string or null | Max 255 |
| bio | string or null | |
| address_line | string or null | Max 255 |
| postal_code | string or null | Max 20 |
| city | string or null | Max 100 |
| country | string or null | Max 100 |
| searching_status | string or null | Max 50 |
| exclude_demographics | boolean | |
| exclude_location | boolean | |

**Success (200):** `{ "message": "Profile updated successfully.", "data": <full profile>, "links": {...} }`

[↑ Back to index](#index)

---

## Student preferences

### Get student preferences

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/preferences` |
| **Auth** | Bearer token + student role |

**Success (200):**
```json
{
  "data": {
    "desired_role_tag_id": 2,
    "hours_per_week_min": 32,
    "hours_per_week_max": 40,
    "max_distance_km": 50,
    "has_drivers_license": true,
    "notes": "Prefer remote work",
    "desired_role_tag": { "id": 2, "name": "Backend Developer", "tag_type": "role" }
  },
  "links": { "self": "..." }
}
```

---

### Update student preferences

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/student/preferences` |
| **Auth** | Bearer token + student role |

**Request body (all optional):**
```json
{
  "desired_role_tag_id": 2,
  "hours_per_week_min": 32,
  "hours_per_week_max": 40,
  "max_distance_km": 50,
  "has_drivers_license": true,
  "notes": "Prefer remote work"
}
```

| Field | Type | Notes |
|-------|------|--------|
| desired_role_tag_id | number or null | Must exist in `tags` |
| hours_per_week_min | number or null | 1–168 |
| hours_per_week_max | number or null | 1–168, must be >= min |
| max_distance_km | number or null | Min 1 |
| has_drivers_license | boolean | |
| notes | string or null | |

**Success (200):** `{ "message": "Preferences updated successfully.", "data": <preferences>, "links": {...} }`

[↑ Back to index](#index)

---

## Student experiences

### List student experiences

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/experiences` |
| **Auth** | Bearer token + student role |

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Intern",
      "company_name": "Acme Corp",
      "start_date": "2024-01-01",
      "end_date": "2024-06-30",
      "description": "Worked on backend systems"
    }
  ],
  "links": { "self": "..." }
}
```

---

### Create student experience

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/student/experiences` |
| **Auth** | Bearer token + student role |

**Request body:**
```json
{
  "title": "Intern",
  "company_name": "Acme Corp",
  "start_date": "2024-01-01",
  "end_date": "2024-06-30",
  "description": "Worked on backend systems"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| title | string | Yes | Max 255 |
| company_name | string | Yes | Max 255 |
| start_date | date | Yes | Format: YYYY-MM-DD |
| end_date | date | No | Must be >= start_date |
| description | string | No | |

**Success (201):** `{ "message": "Experience created successfully.", "data": <experience>, "links": {...} }`

---

### Update student experience

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/student/experiences/{id}` |
| **Auth** | Bearer token + student role |

**Request body (all optional):**
```json
{
  "title": "Junior Developer",
  "company_name": "Acme Corp",
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "description": "Updated description..."
}
```

**Success (200):** `{ "message": "Experience updated successfully.", "data": <experience>, "links": {...} }`  
**Error (404):** Experience not found or not owned by you.

---

### Delete student experience

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/student/experiences/{id}` |
| **Auth** | Bearer token + student role |

**Success (200):** `{ "message": "Experience deleted successfully." }`  
**Error (404):** Experience not found or not owned by you.

[↑ Back to index](#index)

---

## Student languages

### List student languages

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/languages` |
| **Auth** | Bearer token + student role |

**Success (200):**
```json
{
  "data": [
    {
      "language_id": 1,
      "language_level_id": 3,
      "is_active": true,
      "language": { "id": 1, "name": "English" },
      "language_level": { "id": 3, "name": "Fluent" }
    }
  ],
  "links": { "self": "..." }
}
```

---

### Sync student languages

| | |
|---|---|
| **Method** | `PUT` |
| **Path** | `/student/languages` |
| **Auth** | Bearer token + student role |

Replaces all languages for the student. Send the complete list of languages.

**Request body:**
```json
{
  "languages": [
    { "language_id": 1, "language_level_id": 3, "is_active": true },
    { "language_id": 2, "language_level_id": 2 }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| languages | array | Yes | List of language entries |
| languages.*.language_id | number | Yes | Must exist in `languages` table |
| languages.*.language_level_id | number | Yes | Must exist in `language_levels` table |
| languages.*.is_active | boolean | No | Defaults to true |

**Success (200):** `{ "message": "Languages updated successfully.", "data": [...], "links": {...} }`

[↑ Back to index](#index)

---

## Student tags

### List student tags

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/tags` |
| **Auth** | Bearer token + student role |

**Success (200):**
```json
{
  "data": [
    {
      "tag_id": 1,
      "is_active": true,
      "weight": 5,
      "tag": { "id": 1, "name": "PHP", "tag_type": "skill" }
    }
  ],
  "links": { "self": "..." }
}
```

---

### Sync student tags

| | |
|---|---|
| **Method** | `PUT` |
| **Path** | `/student/tags` |
| **Auth** | Bearer token + student role |

Replaces all tags/skills for the student. Send the complete list of tags. This is a **sync** operation—existing tags are removed and replaced with the new list provided.

**Request body:**
```json
{
  "tags": [
    { "tag_id": 1, "is_active": true, "weight": 95 },
    { "tag_id": 2, "is_active": true, "weight": 85 },
    { "tag_id": 13, "is_active": true, "weight": 70 }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| tags | array | Yes | List of tag entries |
| tags.*.tag_id | number | Yes | Must exist in `tags` table |
| tags.*.is_active | boolean | No | Defaults to true |
| tags.*.weight | number | No | 0–100, represents proficiency/priority |

#### Understanding the `weight` field

The `weight` field (0–100) represents the student's **proficiency level** for that skill/tag:

| Weight Range | Proficiency Level |
|--------------|-------------------|
| 90–100 | Expert |
| 70–89 | Advanced |
| 50–69 | Intermediate |
| 30–49 | Beginner |
| 0–29 | Learning |

This weight is used by the matching algorithm to better match students with vacancies. A higher weight indicates stronger proficiency.

**Example – Adding skills with proficiency levels:**
```json
{
  "tags": [
    { "tag_id": 19, "is_active": true, "weight": 95 },
    { "tag_id": 1, "is_active": true, "weight": 90 },
    { "tag_id": 13, "is_active": true, "weight": 60 },
    { "tag_id": 4, "is_active": true, "weight": 40 }
  ]
}
```
In this example, the student is an expert in tag 19 (e.g., Laravel), advanced in tag 1 (e.g., PHP), intermediate in tag 13 (e.g., React), and a beginner in tag 4 (e.g., Python).

**Success (200):**
```json
{
  "message": "Tags updated successfully.",
  "data": [
    {
      "tag_id": 19,
      "is_active": true,
      "weight": 95,
      "tag": { "id": 19, "name": "Laravel", "tag_type": "skill" }
    },
    {
      "tag_id": 1,
      "is_active": true,
      "weight": 90,
      "tag": { "id": 1, "name": "PHP", "tag_type": "skill" }
    }
  ],
  "links": { "self": "https://<your-api-host>/api/v1/student/tags" }
}
```

**Error responses:**

| Status | Reason |
|--------|--------|
| 401 | Not authenticated (missing or invalid JWT) |
| 403 | User is not a student |
| 422 | Validation error (invalid tag_id, weight out of range, etc.) |

<details>
<summary><strong>Postman example</strong></summary>

1. **Login as a student** to get a JWT token:
   ```
   POST /api/v1/auth/login
   Content-Type: application/json
   
   { "email": "student@example.com", "password": "password123" }
   ```

2. **Sync tags** with the token:
   ```
   PUT /api/v1/student/tags
   Authorization: Bearer <your-jwt-token>
   Content-Type: application/json
   Accept: application/json
   
   {
     "tags": [
       { "tag_id": 1, "is_active": true, "weight": 90 },
       { "tag_id": 2, "is_active": true, "weight": 85 },
       { "tag_id": 19, "is_active": true, "weight": 95 }
     ]
   }
   ```

3. **Verify** by listing tags:
   ```
   GET /api/v1/student/tags
   Authorization: Bearer <your-jwt-token>
   Accept: application/json
   ```

</details>

[↑ Back to index](#index)

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
      "banner_url": null,
      "description": null,
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
  "banner_url": null,
  "description": null,
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
| banner_url | string | No | Max 512 |
| description | string | No | |
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
| active_companies_only | boolean | false | If `1` or `true`, only return students and company users whose company is active (useful when listing users for display). Omit to see all users for management. |

**Example:** `GET /coordinator/users?role=student&search=jan&per_page=10`
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

Returns user details. For **students**, includes all related profile data (profile, experiences, tags, languages, preferences, favorite companies, saved vacancies).

**Success (200) – Student:**
```json
{
  "data": {
    "id": 1,
    "role": "student",
    "email": "student@example.com",
    "first_name": "Test",
    "middle_name": null,
    "last_name": "Student",
    "phone": null,
    "created_at": "2025-03-09T12:00:00+00:00",
    "updated_at": "2025-03-09T12:00:00+00:00",
    "student_profile": {
      "user_id": 1,
      "headline": "Junior Developer",
      "bio": "Passionate about coding...",
      "address_line": "Main Street 1",
      "postal_code": "1234AB",
      "city": "Amsterdam",
      "country": "Netherlands",
      "searching_status": "active",
      "exclude_demographics": false,
      "exclude_location": false
    },
    "student_experiences": [
      {
        "id": 1,
        "title": "Intern",
        "company_name": "Acme Corp",
        "start_date": "2024-01-01",
        "end_date": "2024-06-30",
        "description": "Worked on backend systems"
      }
    ],
    "student_tags": [
      {
        "tag_id": 1,
        "is_active": true,
        "weight": 5,
        "tag": { "id": 1, "name": "PHP", "tag_type": "skill" }
      }
    ],
    "student_languages": [
      {
        "language_id": 1,
        "language_level_id": 3,
        "is_active": true,
        "language": { "id": 1, "name": "English" },
        "language_level": { "id": 3, "name": "Fluent" }
      }
    ],
    "student_preferences": {
      "desired_role_tag_id": 2,
      "hours_per_week_min": 32,
      "hours_per_week_max": 40,
      "max_distance_km": 50,
      "has_drivers_license": true,
      "notes": "Prefer remote work",
      "desired_role_tag": { "id": 2, "name": "Backend Developer", "tag_type": "role" }
    },
    "student_favorite_companies": [
      {
        "company_id": 1,
        "company": { "id": 1, "name": "Acme Corp" }
      }
    ],
    "student_saved_vacancies": [
      {
        "vacancy_id": 1,
        "removed_at": null,
        "vacancy": { "id": 1, "title": "Backend Developer", "company_id": 1 }
      }
    ]
  }
}
```

**Success (200) – Company user:**
```json
{
  "data": {
    "id": 2,
    "role": "company",
    "email": "hr@acme.com",
    "first_name": "Jane",
    "middle_name": null,
    "last_name": "Doe",
    "phone": null,
    "created_at": "2025-03-09T12:00:00+00:00",
    "updated_at": "2025-03-09T12:00:00+00:00",
    "company_user": { "company_id": 1, "job_title": "HR Manager" },
    "company": { "id": 1, "name": "Acme Corp" }
  }
}
```

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

**Approving self-registered companies:** Companies that registered via `POST /auth/register/company` start with `is_active: false`. To approve, use `PATCH /coordinator/companies/{id}` with `{ "is_active": true }`. Only active companies appear in `GET /companies` and `GET /vacancies`, and only their users when using `GET /coordinator/users?active_companies_only=1`.

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
