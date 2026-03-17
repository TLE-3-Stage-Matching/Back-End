# API Documentation (v2) – Front-end reference

## API key details

- **v2 – API key + JWT**
    - Base URL: `https://<your-api-host>/api/v2`
    - Auth: API key in `X-API-KEY` header for all routes, plus JWT Bearer token for protected routes where applicable.

**v2 headers example (public call):**

```http
GET /api/v2/companies HTTP/1.1
Host: <your-api-host>
X-API-KEY: <your-api-key>
Accept: application/json
```

**v2 headers example (protected call):**

```http
GET /api/v2/auth/me HTTP/1.1
Host: <your-api-host>
X-API-KEY: <your-api-key>
Authorization: Bearer <jwt-token>
Accept: application/json
```

This document describes **v2** only.

---

# v2 – Front-end reference

**Base URL:** `https://<your-api-host>/api/v2`  
**Content type:** `application/json`  
**Auth:** API key in `X-API-KEY` for all routes, plus JWT Bearer token for protected routes.

For conventions on updating this doc and adding new API functionality, see the internal `CONVENTIONS.md` (backend-only,
not exposed on the front-end).

[↑ Back to index](#index)

---

## Overview – by role (v2)

| Role                  | Capabilities                                                                                                                                                                                                                                                                                                         |
|-----------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Coordinator**       | Register & login; full CRUD on **companies** and on **users** (students + company users); list **vacancies** (all companies) with filters; list **vacancies with match scores** for a given student; **assign/unassign** students to coordinators; **add comments** to vacancies; list companies/users with filters. |
| **Company user**      | Login; view/update **own company**; view/update **own profile** (user + job title); full CRUD on **own company's vacancies** (create/update can add tags by id or create new tags inline; new tags are saved to the DB); **list/update/delete vacancy comments**.                                                    |
| **Student**           | Login; view/update **own profile** (user + student_profile); CRUD **experiences**; manage **preferences**, **languages**, and **tags/skills**; **favorite companies** (add/remove); **saved vacancies** (list/add/remove); **vacancy matching** (top-matches, with-scores, detail, vacancies-with-scores).           |
| **Any authenticated** | List **tags** (for vacancy forms); List **languages** and **language levels**; `GET /auth/me` (company users get `company_user` and `company` loaded); **view student profile by ID** (coordinator or company role).                                                                                                 |

**Tags:** There is no standalone “create tag” endpoint. Tags are created **inline** when a company user creates or
updates a vacancy by sending `{ "name": "...", "tag_type": "..." }` in the vacancy’s `tags` array; the backend uses
`firstOrCreate` and persists new tags to the database.

[↑ Back to index](#index)

---

## Index (v2)

- [Overview – by role (v2)](#overview--by-role-v2)
- [Authentication (v2)](#authentication)
    - [1. Register as coordinator (v2)](#1-register-as-coordinator-stage-coordinator)
    - [2. Register as company (self-registration) (v2)](#2-register-as-company-self-registration)
    - [3. Login (get JWT) (v2)](#3-login-get-jwt)
    - [4. Current user (me) (v2)](#4-current-user-me)
    - [5. Logout (v2)](#5-logout)
    - [6. Refresh token (v2)](#6-refresh-token)
    - [7. Using JWT from front-ends (SPA / mobile) (v2)](#7-using-jwt-from-front-ends-spa--mobile)
- [Company users (v2)](#company-users)
    - [Company-only endpoints (v2)](#company-only-endpoints)
    - [My company (v2)](#my-company)
    - [My profile (v2)](#my-profile)
    - [Tags (v2)](#tags)
    - [List languages (v2)](#languages)
    - [List language levels (v2)](#list-language-levels)
    - [Vacancies (company) (v2)](#vacancies-company)
    - [Company vacancy comments (v2)](#company-vacancy-comments-v2)
- [Students (v2)](#students)
    - [Student-only endpoints (v2)](#student-only-endpoints)
    - [Student profile (v2)](#student-profile)
    - [View student by ID (v2)](#view-student-profile-by-id)
    - [Student favorite companies (v2)](#student-favorite-companies-v2)
    - [Student saved vacancies (v2)](#student-saved-vacancies-v2)
    - [Student vacancy matching (v2)](#student-vacancy-matching-v2)
    - [Student preferences (v2)](#student-preferences)
    - [Student experiences (v2)](#student-experiences)
    - [Student languages (v2)](#student-languages)
    - [Student tags (v2)](#student-tags)
- [Coordinators (v2)](#coordinators)
    - [Coordinator-only endpoints (v2)](#coordinator-only-endpoints)
    - [Companies (coordinator) (v2)](#companies-coordinator)
    - [Users (coordinator) (v2)](#users-coordinator)
    - [Vacancies (coordinator) (v2)](#vacancies-coordinator)
    - [Student vacancies with match scores (coordinator) (v2)](#student-vacancies-with-match-scores-coordinator-v2)
    - [Add comment to vacancy (v2)](#add-comment-to-vacancy-v2)
    - [Student–coordinator assignments (coordinator) (v2)](#studentcoordinator-assignments-coordinator)
- [Public data (v2)](#public-data-v2)
    - [List active companies (v2)](#list-active-companies-v2)
    - [List vacancies (active companies only) (v2)](#list-vacancies-active-companies-only-v2)
- [Dev / Admin (v2)](#dev--admin-v2)
    - [Dev API keys (v2)](#dev-api-keys-v2)
    - [Admin API keys (v2)](#admin-api-keys-v2)

[↑ Back to top](#api-documentation-v2--front-end-reference)

---

## Authentication

### 1. Register as coordinator (stage coordinator)

Creates a new coordinator account. No auth required.

|            |                              |
|------------|------------------------------|
| **Method** | `POST`                       |
| **Path**   | `/auth/register/coordinator` |
| **Auth**   | `X-API-KEY` required         |

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

| Field      | Type   | Required | Notes            |
|------------|--------|----------|------------------|
| email      | string | Yes      | Must be unique   |
| password   | string | Yes      | Min 6 characters |
| first_name | string | Yes      |                  |
| last_name  | string | Yes      |                  |

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
    "links": {
        "self": "..."
    }
}
```

</details>

---

### 2. Register as company (self-registration) (Team A only)

Companies can register themselves. The company is created with `is_active: false` and does not appear in public
company/vacancy listings until a **stage coordinator** approves it by setting `is_active` to `true`
via [Update company](#update-company).

|            |                          |
|------------|--------------------------|
| **Method** | `POST`                   |
| **Path**   | `/auth/register/company` |
| **Auth**   | `X-API-KEY` required     |

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

| Field                   | Type   | Required | Notes                     |
|-------------------------|--------|----------|---------------------------|
| company.name            | string | Yes      | Max 255                   |
| company.industry_tag_id | number | No       | Must exist in `tags`      |
| company.email           | string | No       | Email, max 255            |
| company.phone           | string | No       | Max 50                    |
| company.size_category   | string | No       | Max 50                    |
| company.photo_url       | string | No       |                           |
| location.city           | string | Yes      |                           |
| location.country        | string | Yes      |                           |
| location.address_line   | string | No       |                           |
| location.postal_code    | string | No       | Max 32                    |
| location.lat            | number | No       |                           |
| location.lon            | number | No       |                           |
| user.email              | string | Yes      | Unique                    |
| user.first_name         | string | Yes      | Max 100                   |
| user.middle_name        | string | No       | Max 100                   |
| user.last_name          | string | Yes      | Max 100                   |
| user.phone              | string | No       | Max 50                    |
| password                | string | Yes      | Min 12, must be confirmed |

**Success (201):** Returns `data` (company, user, location) and `meta.token` / `meta.token_type` for immediate login.

---

### 3. Login (get JWT)

Returns a JWT for all subsequent protected requests.

|            |                      |
|------------|----------------------|
| **Method** | `POST`               |
| **Path**   | `/auth/login`        |
| **Auth**   | `X-API-KEY` required |

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
    "token_type": "Bearer",
    "data": {
        "id": 1,
        "role": "coordinator",
        "email": "...",
        "first_name": "...",
        ...
    }
}
```

`data` is the authenticated user (same shape as `GET /auth/me`). Company users include `company_user` and `company`;
students include `student_profile`.

</details>

**Error (401):** `{ "message": "Invalid credentials" }`

**Usage:** Send the token on every protected request:

```http
Authorization: Bearer <token>
```

---

### 4. Current user (me)

|            |                                     |
|------------|-------------------------------------|
| **Method** | `GET`                               |
| **Path**   | `/auth/me`                          |
| **Auth**   | `X-API-KEY` + Bearer token required |

**Success (200):** `{ "data": <user object> }` (full user as returned by Laravel auth).

---

### 5. Logout

|            |                                     |
|------------|-------------------------------------|
| **Method** | `POST`                              |
| **Path**   | `/auth/logout`                      |
| **Auth**   | `X-API-KEY` + Bearer token required |

**Success (200):** `{ "message": "Logged out" }`

---

### 6. Refresh token

|            |                                     |
|------------|-------------------------------------|
| **Method** | `POST`                              |
| **Path**   | `/auth/refresh`                     |
| **Auth**   | `X-API-KEY` + Bearer token required |

**Success (200):** Same shape as login: `{ "token": "...", "token_type": "Bearer" }`

[↑ Back to index](#index)

---

### 7. Using JWT from front-ends (SPA / mobile)

This section explains **how to implement authentication in a front-end** (React, Vue, Angular, mobile, etc.), **how to
send the Bearer token**, how **refresh** works, and how to **prevent deep-linking into protected pages**.

#### 7.1 Basic login flow

1. **Show a login form** that posts to `POST /api/v2/auth/login` with `email` and `password`.
2. On **success**, the API returns:
   ```json
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     "token_type": "Bearer"
   }
   ```
3. **Store the token** somewhere accessible to your HTTP client:
    - For SPAs, a common (simple) option is `localStorage` (e.g. `localStorage.setItem('token', token)`).
    - For higher security you can keep it **in memory only** (Redux/Pinia/Zustand/etc.) and re-login on full refresh.
      This avoids XSS-based token theft but requires a new login when the user reloads the page.
4. After login, **navigate to your app’s protected area** (e.g. `/dashboard`) and start using the token on all protected
   calls.

#### 7.2 Sending the Bearer token

For every protected API request, include the token as a **Bearer** token in the `Authorization` header:

```http
Authorization: Bearer <token>
```

**Example with `fetch` (vanilla JS):**

```js
const token = localStorage.getItem('token');

const res = await fetch('/api/v2/company', {
    headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
    },
});
```

#### 7.3 How refresh works

- JWTs are **time-limited**. When a token expires, protected endpoints will start returning **401 Unauthorized**.
- To keep the user logged in without showing the login screen again, you can call `POST /api/v2/auth/refresh` with the *
  *current (still-present) token** in the `Authorization` header.
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

    const res = await fetch(`/api/v2${path}`, {
        ...options,
        headers: {
            ...(options.headers || {}),
            'Content-Type': 'application/json',
            ...(token ? {Authorization: `Bearer ${token}`} : {}),
        },
    });

    // If token expired, try refresh once
    if (res.status === 401 && token) {
        const refreshRes = await fetch('/api/v2/auth/refresh', {
            method: 'POST',
            headers: {Authorization: `Bearer ${token}`},
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
        const retryRes = await fetch(`/api/v2${path}`, {
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

“Deep-linking” here means typing a protected URL directly into the browser (e.g. `/app/company`) or refreshing on it
while **not authenticated**. To prevent this, your front-end should:

1. **Track auth state** (e.g. `isAuthenticated`, plus the current token).
2. **Protect routes** with guards that:
    - Check if there is a token.
    - Optionally call `/auth/me` once on app startup to validate the token and load the user.
    - Redirect to `/login` when there is no valid token.

**Example (React Router-like pseudo-code):**

```jsx
function PrivateRoute({children}) {
    const token = localStorage.getItem('token');

    if (!token) {
        // Not logged in →send to login
        return <Navigate to="/login" replace/>;
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
- When the token is missing or invalid, users are **always redirected to the login page**, even if they try to deep-link
  or refresh on a protected route.

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

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/company`                                |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Returns the authenticated user’s company.

**Success (200):** `{ "data": <company object>, "links": { "self": "..." } }`

---

### Update my company

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/company`                                |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Update your company. Only include fields you want to change.

<details>
<summary><strong>Request body (all optional)</strong></summary>

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

</details>

| Field           | Type           | Notes                |
|-----------------|----------------|----------------------|
| name            | string         | Max 255              |
| industry_tag_id | number or null | Must exist in `tags` |
| email           | string         | Max 255              |
| phone           | string         | Max 50               |
| size_category   | string         | Max 50               |
| photo_url       | string         |                      |
| banner_url      | string         | Max 512              |
| description     | string         |                      |
| is_active       | boolean        |                      |

<details>
<summary><strong>Success (200) – Response body</strong></summary>

`{ "data": <updated company>, "links": { "self": "..." } }`

</details>

[↑ Back to index](#index)

---

## My profile

Company users can view and update **their own profile** (user fields and job title).

### Get my profile

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/company/profile`                        |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Returns the authenticated user’s profile including `company_user` and `company`.  
**Note:** `GET /auth/me` also returns the current user and, for company users, includes `company_user` and `company`when
loaded.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

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
        "company_user": {
            "company_id": 1,
            "job_title": "HR Manager"
        },
        "company": {
            "id": 1,
            "name": "Acme Corp",
            ...
        }
    },
    "links": {
        "self": "..."
    }
}
```

</details>

---

### Update my profile

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/company/profile`                        |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Update your user fields and/or job title. Only include fields you want to change. Omit `password` or send `null` to
leave it unchanged.

<details>
<summary><strong>Request body (all optional)</strong></summary>

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

</details>

| Field       | Type           | Notes                                   |
|-------------|----------------|-----------------------------------------|
| first_name  | string         | Max 100                                 |
| middle_name | string or null | Max 100                                 |
| last_name   | string         | Max 100                                 |
| phone       | string or null | Max 50                                  |
| email       | string         | Must be unique (excluding current user) |
| password    | string or null | Min 6; omit or null to keep current     |
| job_title   | string or null | Max 255                                 |

**Success (200):** `{ "data": <updated profile>, "links": { "self": "..." } }`

[↑ Back to index](#index)

---

## Tags

Used when creating vacancies: company users can **select existing tags** (from this list) or **create new tags** inline
in the vacancy payload.

### List tags

|            |                                     |
|------------|-------------------------------------|
| **Method** | `GET`                               |
| **Path**   | `/tags`                             |
| **Auth**   | `X-API-KEY` + Bearer token required |

**Query parameters:**

| Param    | Type   | Description                                              |
|----------|--------|----------------------------------------------------------|
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
    "links": {
        "self": "..."
    }
}
```

[↑ Back to index](#index)

---

## Languages

Master list of all available languages. Use for student language selection (and any other dropdown that needs the full
list).

### List languages

|            |                                     |
|------------|-------------------------------------|
| **Method** | `GET`                               |
| **Path**   | `/languages`                        |
| **Auth**   | `X-API-KEY` + Bearer token required |

**Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "English"
        },
        {
            "id": 2,
            "name": "Dutch"
        }
    ],
    "links": {
        "self": "..."
    }
}
```

### List language levels

|            |                                     |
|------------|-------------------------------------|
| **Method** | `GET`                               |
| **Path**   | `/language-levels`                  |
| **Auth**   | `X-API-KEY` + Bearer token required |

Master list of language proficiency levels (e.g. A1–C2). Use with `GET /languages` for the student sync-languages form.

**Success (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "A1"
        },
        {
            "id": 2,
            "name": "A2"
        },
        {
            "id": 3,
            "name": "B1"
        }
    ],
    "links": {
        "self": "..."
    }
}
```

[↑ Back to index](#index)

---

## Vacancies (company)

Company users create and list vacancies for their own company. Each vacancy can have **tags**: either by **selecting
existing tag IDs** (from `GET /tags`) or by **creating new tags** by sending `name` and `tag_type` in the payload.

### List company vacancies

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/company/vacancies`                      |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Returns all vacancies for the authenticated user’s company.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

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
                    "tag": {
                        "id": 1,
                        "name": "PHP",
                        "tag_type": "skill"
                    }
                }
            ]
        }
    ],
    "links": {
        "self": "..."
    }
}
```

</details>

---

### Create vacancy

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `POST`                                    |
| **Path**   | `/company/vacancies`                      |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

<details>
<summary><strong>Request body (JSON)</strong></summary>

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
        {
            "id": 1
        },
        {
            "name": "Laravel",
            "tag_type": "skill"
        },
        {
            "id": 2,
            "requirement_type": "skill",
            "importance": 1
        }
    ]
}
```

</details>

| Field             | Type   | Required | Notes                                                    |
|-------------------|--------|----------|----------------------------------------------------------|
| title             | string | Yes      | Max 255                                                  |
| location_id       | number | No       | Must be a location of your company (`company_locations`) |
| hours_per_week    | number | No       | 1–168                                                    |
| description       | string | No       |                                                          |
| offer_text        | string | No       |                                                          |
| expectations_text | string | No       |                                                          |
| status            | string | No       | Max 32                                                   |
| tags              | array  | No       | List of tag references (see below)                       |

**Tags array** – each item is either:

- **Existing tag:** `{ "id": <tag_id> }`. Optional per item: `requirement_type` (string, max 16, default `"skill"`),
  `importance` (number).
- **New tag:** `{ "name": "<name>", "tag_type": "<tag_type>" }`. The tag is created if it doesn’t exist (matched by
  name + tag_type). Optional: `requirement_type`, `importance`.

<details>
<summary><strong>Success (201) – Response body</strong></summary>

`{ "data": <vacancy with location and vacancy_requirements loaded>, "links": { "self": "..." } }`

</details>

**Error (422):** Validation errors, or `"Location does not belong to your company."` if `location_id` is not one of your
company’s locations.

---

### Get vacancy

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/company/vacancies/{id}`                 |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Returns a single vacancy. The vacancy must belong to the authenticated user's company; otherwise **404** is returned.

<details>
<summary><strong>Success (200) – Response body</strong></summary>

`{ "data": <vacancy with location and vacancy_requirements.tag>, "links": { "self": "...", "collection": "..." } }`

</details>

**Error (404):** Vacancy not found or not owned by your company.

---

### Update vacancy

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/company/vacancies/{id}`                 |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Update vacancy fields and/or replace its tags. Only include fields you want to change. To update tags, send a `tags`
array (same format as [Create vacancy](#create-vacancy)); existing requirements are replaced. Omit `tags` to leave tags
unchanged.

<details>
<summary><strong>Request body (all fields optional)</strong></summary>

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
        {
            "id": 1
        },
        {
            "name": "Laravel",
            "tag_type": "skill"
        }
    ]
}
```

</details>

| Field             | Type           | Notes                                                            |
|-------------------|----------------|------------------------------------------------------------------|
| title             | string         | Max 255                                                          |
| location_id       | number or null | Must be a location of your company, or null to clear             |
| hours_per_week    | number         | 1–168                                                            |
| description       | string         |                                                                  |
| offer_text        | string         |                                                                  |
| expectations_text | string         |                                                                  |
| status            | string         | Max 32                                                           |
| tags              | array          | Same format as create; replaces all existing tags on the vacancy |

**Success (200):** `{ "data": <updated vacancy>, "links": { "self": "...", "collection": "..." } }`  
**Error (404):** Vacancy not found or not owned by your company.  
**Error (422):** Validation errors, or `"Location does not belong to your company."`

---

### Delete vacancy

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `DELETE`                                  |
| **Path**   | `/company/vacancies/{id}`                 |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Deletes the vacancy. The vacancy must belong to the authenticated user's company; otherwise **404** is returned.

**Success (204):** No content.  
**Error (404):** Vacancy not found or not owned by your company.

### Company vacancy comments (v2)

Company users can list, update, and delete comments on vacancies that belong to their company. Comments are created by
coordinators via `POST /coordinator/vacancies/{vacancy}/comments`.

#### List vacancy comments

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/company/vacancies/{vacancy}/comments`   |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

List all comments on a vacancy. The vacancy must belong to the authenticated user's company; otherwise **403** is
returned.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "id": 1,
            "vacancy_id": 1,
            "author_user_id": 5,
            "comment": "Consider adding more detail on remote work.",
            "created_at": "2025-03-10T12:00:00+00:00",
            "updated_at": "2025-03-10T12:00:00+00:00"
        }
    ],
    "links": {
        "self": "https://<host>/api/v2/company/vacancies/1/comments"
    }
}
```

</details>

**Error (403):** `{ "message": "Forbidden" }` – Vacancy does not belong to your company.

---

#### Update vacancy comment

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PATCH`                                   |
| **Path**   | `/company/vacancies/comments/{comment}`   |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Update the text of a comment. The comment must belong to a vacancy of your company; otherwise **403** is returned.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
    "comment": "Updated feedback for the company."
}
```

</details>

| Field   | Type   | Required | Notes             |
|---------|--------|----------|-------------------|
| comment | string | Yes      | New comment text. |

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "message": "Comment updated successfully.",
    "data": {
        "id": 1,
        "vacancy_id": 1,
        "author_user_id": 5,
        "comment": "Updated feedback for the company.",
        "created_at": "...",
        "updated_at": "..."
    },
    "links": {
        "self": "https://<host>/api/v2/company/vacancies/comments/1"
    }
}
```

</details>

**Error (403):** `{ "message": "Forbidden" }` – Comment's vacancy does not belong to your company.  
**Error (404):** Comment not found.  
**Error (422):** Validation error (e.g. missing `comment`).

---

#### Delete vacancy comment

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `DELETE`                                  |
| **Path**   | `/company/vacancies/comments/{comment}`   |
| **Auth**   | `X-API-KEY` + Bearer token + company role |

Delete a comment. The comment must belong to a vacancy of your company; otherwise **403** is returned.

**Success (200):**

```json
{
    "message": "Comment deleted successfully.",
    "links": {
        "self": "https://<host>/api/v2/company/vacancies/comments/1"
    }
}
```

**Error (403):** `{ "message": "Forbidden" }` – Comment's vacancy does not belong to your company.  
**Error (404):** Comment not found.

[↑ Back to index](#index)

---

### Match choices (company) (v2)

Company users can list match choices for their company's vacancies and approve or reject them with a decision note. Only choices with status `requested` or `shortlisted` can be approved or rejected.

#### List match choices (company)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/company/match-choices` |
| **Auth** | `X-API-KEY` + Bearer token + company role |

Returns all match choices for vacancies belonging to the authenticated user's company. Optional query parameters: `vacancy_id`, `status`.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
  "data": [
    {
      "id": 1,
      "student_user_id": 2,
      "vacancy_id": 10,
      "status": "requested",
      "student_note": "Interested in backend focus",
      "decided_by_user_id": null,
      "decided_at": null,
      "decision_note": null,
      "created_at": "2025-03-10T12:00:00+00:00",
      "updated_at": "2025-03-10T12:00:00+00:00",
      "student": {
        "id": 2,
        "email": "student@example.com",
        "first_name": "Jane",
        "last_name": "Doe"
      },
      "vacancy": { "id": 10, "title": "Backend developer", "company_id": 5 }
    }
  ],
  "links": { "self": "https://<host>/api/v2/company/match-choices" }
}
```

</details>

---

#### Approve match choice (company)

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/company/match-choices/{choice}/approve` |
| **Auth** | `X-API-KEY` + Bearer token + company role |

Approve a match choice. The choice must belong to a vacancy of your company and have status `requested` or `shortlisted`. Path parameter `choice` is the choice ID.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "decision_note": "We would like to invite this student for an interview."
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| decision_note | string | Yes | Reason for the decision (non-empty). |

**Success (200):** `{ "message": "Match choice approved.", "data": <choice with student and vacancy>, "links": {...} }`

**Error (403):** Choice already decided or withdrawn.  
**Error (404):** Choice not found or vacancy not owned by your company.

---

#### Reject match choice (company)

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/company/match-choices/{choice}/reject` |
| **Auth** | `X-API-KEY` + Bearer token + company role |

Reject a match choice. Same ownership and status rules as approve. Path parameter `choice` is the choice ID.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "decision_note": "We have filled this position internally."
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| decision_note | string | Yes | Reason for the decision (non-empty). |

**Success (200):** `{ "message": "Match choice rejected.", "data": <choice>, "links": {...} }`

**Error (403):** Choice already decided or withdrawn.  
**Error (404):** Choice not found or vacancy not owned by your company.

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

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/profile`                        |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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
        "student_experiences": [
            ...
        ],
        "student_tags": [
            ...
        ],
        "student_languages": [
            ...
        ],
        "student_preferences": {
            ...
        }
    },
    "links": {
        "self": "..."
    }
}
```

---

### Update student profile

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/student/profile`                        |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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

| Field                | Type           | Notes                                   |
|----------------------|----------------|-----------------------------------------|
| first_name           | string         | Max 100                                 |
| middle_name          | string or null | Max 100                                 |
| last_name            | string         | Max 100                                 |
| phone                | string or null | Max 50                                  |
| email                | string         | Must be unique (excluding current user) |
| password             | string or null | Min 6; omit or null to keep current     |
| headline             | string or null | Max 255                                 |
| bio                  | string or null |                                         |
| address_line         | string or null | Max 255                                 |
| postal_code          | string or null | Max 20                                  |
| city                 | string or null | Max 100                                 |
| country              | string or null | Max 100                                 |
| searching_status     | string or null | Max 50                                  |
| exclude_demographics | boolean        |                                         |
| exclude_location     | boolean        |                                         |

**Success (200):** `{ "message": "Profile updated successfully.", "data": <full profile>, "links": {...} }`

---

### View student profile (by ID)

**Allowed roles:** Coordinator, Company user  
Coordinators and company users can view any student's full profile including experiences, tags, languages, and
preferences.

|            |                                                          |
|------------|----------------------------------------------------------|
| **Method** | `GET`                                                    |
| **Path**   | `/student/{student}`                                     |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator or company role |

**URL parameters:**

- `student` (number): The student's user ID.

**Success (200):**

```json
{
    "data": {
        "id": 1,
        "first_name": "John",
        "middle_name": null,
        "last_name": "Doe",
        "email": "john.doe@example.com",
        "profile_photo_url": null,
        "student_profile": {
            "id": 1,
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
        "experiences": [
            {
                "id": 1,
                "user_id": 1,
                "title": "Intern",
                "company_name": "Acme Corp",
                "start_date": "2024-01-01",
                "end_date": "2024-06-30",
                "description": "Worked on backend systems"
            }
        ],
        "tags": [
            {
                "id": 1,
                "user_id": 1,
                "tag_id": 5,
                "weight": 3,
                "tag": {
                    "id": 5,
                    "name": "PHP",
                    "tag_type": "skill"
                }
            }
        ],
        "languages": [
            {
                "id": 1,
                "user_id": 1,
                "language_id": 1,
                "language_level_id": 2,
                "language": {
                    "id": 1,
                    "name": "English"
                },
                "language_level": {
                    "id": 2,
                    "name": "Intermediate"
                }
            }
        ],
        "preferences": {
            "id": 1,
            "user_id": 1,
            "desired_role_tag_id": 2,
            "hours_per_week_min": 32,
            "hours_per_week_max": 40,
            "max_distance_km": 50,
            "has_drivers_license": true,
            "notes": "Prefer remote work",
            "desired_role_tag": {
                "id": 2,
                "name": "Backend Developer",
                "tag_type": "role"
            }
        }
    },
    "links": {
        "self": "/api/v2/student/1"
    }
}
```

**Error (403):** `{ "message": "Unauthorized" }` – User is not a coordinator or company user.  
**Error (404):** `{ "message": "User is not a student" }` – The specified user exists but is not a student.

[↑ Back to index](#index)

---

### Student favorite companies (v2)

Students can add companies to a favorites list and remove them. No duplicate entries per student–company pair.

#### List favorite companies

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/favorite-companies`             |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns the authenticated student's favorite companies, most recently added first.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "id": 1,
            "student_user_id": 1,
            "company_id": 5,
            "created_at": "2025-03-10T12:00:00+00:00",
            "company": {
                "id": 5,
                "name": "Acme Corp"
            }
        }
    ],
    "links": {
        "self": "https://<host>/api/v2/student/favorite-companies"
    }
}
```

</details>

---

#### Add company to favorites

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `POST`                                    |
| **Path**   | `/student/favorite-companies`             |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Add a company to the student's favorites. If the company is already in favorites, the existing record is returned (
idempotent).

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
    "company_id": 5
}
```

</details>

| Field      | Type   | Required | Notes                      |
|------------|--------|----------|----------------------------|
| company_id | number | Yes      | Must exist in `companies`. |

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
    "message": "Company added to favorites.",
    "data": {
        "id": 1,
        "student_user_id": 1,
        "company_id": 5,
        "created_at": "2025-03-10T12:00:00+00:00"
    },
    "links": {
        "self": "https://<host>/api/v2/student/favorite-companies/5",
        "collection": "https://<host>/api/v2/student/favorite-companies"
    }
}
```

</details>

**Error (422):** Validation error (e.g. missing or invalid `company_id`, or company does not exist).

---

#### Remove company from favorites

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `DELETE`                                  |
| **Path**   | `/student/favorite-companies/{companyId}` |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Remove a company from the student's favorites. Path parameter `companyId` is the company ID.

**Success (200):**

```json
{
    "message": "Company removed from favorites."
}
```

**Error (404):** `{ "message": "Favorite company not found." }` – No favorite record for this student and company.

[↑ Back to index](#index)

---

### Student vacancy matching (v2)

Base path `/api/v2`; auth: `X-API-KEY` + Bearer token + student role. Only vacancies from **active** companies are
considered.

| Method | Path                                  | Description                                                                                                                         |
|--------|---------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|
| GET    | `/student/vacancies/top-matches`      | Top 3 best-matching vacancies for the student.                                                                                      |
| GET    | `/student/vacancies/with-scores`      | All eligible vacancies with score details, sorted by score.                                                                         |
| GET    | `/student/vacancies/{vacancy}/detail` | Single vacancy score explanation (breakdown, must_have_misses, human_explanation).                                                  |
| GET    | `/student/vacancies-with-scores`      | List vacancies with match score and subscores (must_have, nice_to_have, combined, penalty); pagination, optional `industry_tag_id`. |

For how the score is computed, see [How student-vacancy matching works](#how-student-vacancy-matching-works) below.

#### Top matches (top 3)

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/vacancies/top-matches`          |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns the top 3 vacancies for the authenticated student, scored using the student-facing algorithm (must-have /
nice-to-have + penalty). Only open vacancies from active companies are included.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "vacancy_id": 10,
            "title": "Backend developer",
            "company": "Acme Corp",
            "score": 85
        },
        {
            "vacancy_id": 12,
            "title": "Junior PHP developer",
            "company": "Tech Co",
            "score": 72
        }
    ]
}
```

</details>

---

#### All vacancies with scores

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/vacancies/with-scores`          |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns all eligible vacancies (open, from active companies) with score and breakdown, sorted by score descending. No
pagination; use `GET /student/vacancies-with-scores` for paginated results.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "vacancy_id": 10,
            "title": "Backend developer",
            "company": "Acme Corp",
            "score": 85,
            "must_have_misses": [
                3,
                7
            ],
            "breakdown": {
                "s_mh": 0.9,
                "s_nth": 0.7,
                "s_tags": 0.86,
                "penalty": 0.05
            }
        }
    ]
}
```

</details>

---

#### Single vacancy detail (score explanation)

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/vacancies/{vacancy}/detail`     |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns a detailed score explanation for one vacancy: breakdown, must_have_misses, tag-level details, and a
human-readable summary with "what you match well" and "what to improve next". The vacancy must belong to an active
company; otherwise **404** is returned.

**URL parameters:** `vacancy` – vacancy ID.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": {
        "vacancy": {
            "id": 10,
            "title": "Backend developer",
            "company": "Acme Corp"
        },
        "score": 85,
        "breakdown": {
            "s_mh": 0.9,
            "s_nth": 0.7,
            "s_tags": 0.86,
            "penalty": 0.05
        },
        "must_have_misses": [
            {
                "tag_id": 3,
                "tag_name": "PHP"
            }
        ],
        "nice_to_have_misses": [
            {
                "tag_id": 5,
                "tag_name": "Laravel",
                "importance": 4
            }
        ],
        "tags": {
            "all": [],
            "must_haves": [],
            "nice_to_haves": []
        },
        "human_explanation": {
            "summary": "You match this vacancy with a score of 85. You meet 4/5 must-have tags and 2/3 nice-to-have tags.",
            "what_you_match_well": [
                "PHP",
                "MySQL",
                "Git"
            ],
            "what_to_improve_next": [
                "Laravel",
                "REST APIs"
            ],
            "tips": [
                "Focus first on missing must-have tags..."
            ]
        },
        "explanation": {
            "formula": {
                "match_multiplier": "m_k = 1 + (w_k - 3) / 20 when student has the tag, else 0",
                "importance_normalized": "i_hat = importance / 5",
                "must_have_score": "S_MH = weighted average of must-have tag matches (or 1.0 when none)",
                "nice_to_have_score": "S_NTH = weighted average of nice-to-have tag matches (or 1.0 when none)",
                "combined": "S_tags = 0.8 * S_MH + 0.2 * S_NTH",
                "penalty": "P = (missing_must_haves / total_must_haves) * 0.25",
                "final": "score = clamp((S_tags - P) * 100, 0, 100)"
            }
        }
    },
    "links": {
        "self": "..."
    }
}
```

</details>

**Error (404):** `{ "message": "Vacancy not found." }` – Vacancy does not exist or its company is not active.

---

#### Vacancies with scores (paginated)

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/vacancies-with-scores`          |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns vacancies from active companies with match score and subscores, sorted by score descending, with pagination.

**Query parameters:**

| Param           | Type   | Default | Description                                                             |
|-----------------|--------|---------|-------------------------------------------------------------------------|
| per_page        | number | 15      | Pagination size.                                                        |
| page            | number | 1       | Page number.                                                            |
| industry_tag_id | number | —       | Optional. Filter to vacancies whose company has this `industry_tag_id`. |

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "vacancy": {
                "id": 10,
                "company_id": 5,
                "location_id": null,
                "title": "Backend developer",
                "hours_per_week": 40,
                "description": "...",
                "status": "open",
                "created_at": "...",
                "updated_at": "...",
                "company": {
                    "id": 5,
                    "name": "Acme Corp"
                }
            },
            "match_score": 85,
            "subscores": {
                "must_have": {
                    "score": 0.9,
                    "explanation": "Weighted average match for must-have tags."
                },
                "nice_to_have": {
                    "score": 0.7,
                    "explanation": "Weighted average match for nice-to-have tags."
                },
                "combined": {
                    "score": 0.86,
                    "explanation": "Combined score before penalty (0.8 * must-have + 0.2 * nice-to-have)."
                },
                "penalty": {
                    "score": 0.05,
                    "explanation": "Penalty for missing must-have tags."
                }
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    },
    "links": {
        "self": "..."
    }
}
```

</details>

---

#### How student-vacancy matching works

There are **two different matching systems**: one for student-facing endpoints and one for the coordinator view. Both
produce a **0–100 score**, but they are tuned for different UIs.

**Student-facing matching** (used by `top-matches`, `with-scores`, `{vacancy}/detail`, and `vacancies-with-scores`):

- **What it does:** Produces a single **fit score (0–100)** plus a few subscores that explain how well a student’s tags
  line up with a vacancy’s must-haves and nice-to-haves.
- **Inputs (conceptual):**
    - The student’s tags with a **proficiency weight 1–5** (how strong the skill/trait is for the student).
    - The vacancy’s requirements, where each tag is marked as a **must-have** or **nice-to-have** and has an *
      *importance 1–5**.
- **Subscores you will see in responses:**
    - `subscores.must_have.score` – how well the student matches the vacancy’s must-have tags (weighted by importance).
    - `subscores.nice_to_have.score` – how well the student matches the nice-to-have tags.
    - `subscores.combined.score` – combined tag match before any penalty is applied.
    - `subscores.penalty.score` – penalty applied when must-have tags are missing.
    - `must_have_misses` – list/count of must-have tags the student is missing.
- **Final score in responses:**
    - `match_score` (0–100) – the main number to show in the UI (e.g. as a percentage or progress bar). Higher is
      better.
- **Explanations:**
    - For detail endpoints, `breakdown`, `subscores.*.explanation`, `human_explanation`, and/or `explanation.formula`can
      be shown in tooltips or expandable panels to explain why a score is high or low (e.g. “Missing 2 must-have tags”).

**Coordinator-facing matching** (used by `GET /coordinator/students/{user}/vacancies-with-scores`):

- **What it does:** Produces a **vacancy-centric fit score (0–100)** plus a breakdown **per tag category** (for example
  “skills” vs “traits”) so coordinators can advise students.
- **Inputs (conceptual):**
    - The student’s tags with weights (how strong each tag is for the student).
    - The vacancy’s tags with importances (how important each tag is for the vacancy).
- **How to read the score:**
    - `match_score` (0–100) – overall fit between the student and the vacancy, taking both skill and trait tags into
      account.
    - `subscores` – category-based scores (for example skill vs trait) and explanations describing which tags contribute
      most to the match.
- **UI guidance:**
    - Use `match_score` for the main indicator (e.g. sorting vacancies, badges like “Strong match”).
    - Use the per-category subscores and explanations to show **why** a match is strong or weak (for example “skills are
      a strong match, traits are weaker”).

**Why two systems:** The **student UI** focuses on a simple “fit” score plus clear messaging around missing must-haves,
while the **coordinator view** focuses on category-level insights (skills/traits, etc.) for coaching conversations. Both
expose a 0–100 `match_score` so front-ends can treat them consistently.

[↑ Back to index](#index)

---

### Student saved vacancies (v2)

Students can save vacancies to a list and remove them. Saved vacancies with `removed_at` set are excluded from the list.

#### List saved vacancies

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/saved-vacancies`                |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Returns the authenticated student's saved vacancies (only those not removed), most recently saved first.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "id": 1,
            "student_user_id": 1,
            "vacancy_id": 10,
            "created_at": "2025-03-10T12:00:00+00:00",
            "removed_at": null,
            "vacancy": {
                "id": 10,
                "title": "Backend developer",
                "company_id": 5
            }
        }
    ],
    "links": {
        "self": "https://<host>/api/v2/student/saved-vacancies"
    }
}
```

</details>

---

#### Add vacancy to saved list

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `POST`                                    |
| **Path**   | `/student/saved-vacancies`                |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Save a vacancy. If the student already had it saved and had removed it, the record is restored (`removed_at` set to
null). If never saved, a new record is created.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
    "vacancy_id": 10
}
```

</details>

| Field      | Type   | Required | Notes                      |
|------------|--------|----------|----------------------------|
| vacancy_id | number | Yes      | Must exist in `vacancies`. |

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
    "message": "Vacancy saved successfully.",
    "data": {
        "id": 1,
        "student_user_id": 1,
        "vacancy_id": 10,
        "created_at": "2025-03-10T12:00:00+00:00",
        "removed_at": null
    },
    "links": {
        "self": "https://<host>/api/v2/student/saved-vacancies/10",
        "collection": "https://<host>/api/v2/student/saved-vacancies"
    }
}
```

</details>

**Error (422):** Validation error (e.g. missing or invalid `vacancy_id`, or vacancy does not exist).

---

#### Remove vacancy from saved list

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `DELETE`                                  |
| **Path**   | `/student/saved-vacancies/{vacancyId}`    |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Remove a vacancy from the saved list (sets `removed_at`; the record is kept). Path parameter `vacancyId` is the vacancy
ID.

**Success (200):**

```json
{
    "message": "Saved vacancy removed successfully."
}
```

**Error (404):** `{ "message": "Saved vacancy not found." }` – No active saved record for this student and vacancy.

[↑ Back to index](#index)

---

### Student match choices (v2)

Students can choose a vacancy as a match choice and leave a note explaining why. One choice per student–vacancy pair. Only choices with status `requested` or `shortlisted` can be updated or withdrawn; once a coordinator or company approves or rejects, the choice is final.

#### List my match choices

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/match-choices` |
| **Auth** | `X-API-KEY` + Bearer token + student role |

Returns the authenticated student's match choices, most recent first. Optional query parameters: `status`, `vacancy_id`.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
  "data": [
    {
      "id": 1,
      "student_user_id": 1,
      "vacancy_id": 10,
      "status": "requested",
      "student_note": "Interested in backend focus",
      "decided_by_user_id": null,
      "decided_at": null,
      "decision_note": null,
      "created_at": "2025-03-10T12:00:00+00:00",
      "updated_at": "2025-03-10T12:00:00+00:00",
      "vacancy": {
        "id": 10,
        "title": "Backend developer",
        "company_id": 5,
        "company": { "id": 5, "name": "Acme Corp" }
      }
    }
  ],
  "links": { "self": "https://<host>/api/v2/student/match-choices" }
}
```

</details>

---

#### Create match choice

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/student/match-choices` |
| **Auth** | `X-API-KEY` + Bearer token + student role |

Create a choice for a vacancy with an optional student note. The vacancy must exist and belong to an active company. Only one choice per student–vacancy pair.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "vacancy_id": 10,
  "student_note": "I chose this because of the tech stack."
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| vacancy_id | number | Yes | Must exist in `vacancies`; vacancy's company must be active. |
| student_note | string | No | Optional note explaining why the student chose this vacancy. |

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
  "message": "Match choice created successfully.",
  "data": {
    "id": 1,
    "student_user_id": 1,
    "vacancy_id": 10,
    "status": "requested",
    "student_note": "I chose this because of the tech stack.",
    "decided_by_user_id": null,
    "decided_at": null,
    "decision_note": null,
    "created_at": "2025-03-10T12:00:00+00:00",
    "updated_at": "2025-03-10T12:00:00+00:00",
    "vacancy": {
      "id": 10,
      "title": "Backend developer",
      "company_id": 5,
      "company": { "id": 5, "name": "Acme Corp" }
    }
  },
  "links": {
    "self": "https://<host>/api/v2/student/match-choices/1",
    "collection": "https://<host>/api/v2/student/match-choices"
  }
}
```

</details>

**Error (422):** Validation error; or vacancy not found / company not active; or a choice for this student and vacancy already exists.

---

#### Get one match choice

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/student/match-choices/{choice}` |
| **Auth** | `X-API-KEY` + Bearer token + student role |

Returns a single match choice. Only the owning student can access it. Path parameter `choice` is the choice ID.

**Success (200):** `{ "data": <choice object with vacancy and company>, "links": {...} }`

**Error (404):** `{ "message": "Match choice not found." }`

---

#### Update match choice (note or withdraw)

| | |
|---|---|
| **Method** | `PUT` or `PATCH` |
| **Path** | `/student/match-choices/{choice}` |
| **Auth** | `X-API-KEY` + Bearer token + student role |

Update the student note and/or set status to `withdrawn`. Only allowed when the choice has not yet been decided (no `decided_at`). Path parameter `choice` is the choice ID.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "student_note": "Updated reason.",
  "status": "withdrawn"
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| student_note | string | No | New note (optional). |
| status | string | No | Only `"withdrawn"` allowed; withdraws the choice. |

**Success (200):** `{ "message": "Match choice updated successfully.", "data": <choice>, "links": {...} }`

**Error (403):** Choice already decided or not in a state that can be updated. **Error (404):** Match choice not found.

[↑ Back to index](#index)

---

## Student preferences

### Get student preferences

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/preferences`                    |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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
        "desired_role_tag": {
            "id": 2,
            "name": "Backend Developer",
            "tag_type": "role"
        }
    },
    "links": {
        "self": "..."
    }
}
```

---

### Update student preferences

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/student/preferences`                    |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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

| Field               | Type           | Notes                 |
|---------------------|----------------|-----------------------|
| desired_role_tag_id | number or null | Must exist in `tags`  |
| hours_per_week_min  | number or null | 1–168                 |
| hours_per_week_max  | number or null | 1–168, must be >= min |
| max_distance_km     | number or null | Min 1                 |
| has_drivers_license | boolean        |                       |
| notes               | string or null |                       |

**Success (200):** `{ "message": "Preferences updated successfully.", "data": <preferences>, "links": {...} }`

[↑ Back to index](#index)

---

## Student experiences

### List student experiences

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/experiences`                    |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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
    "links": {
        "self": "..."
    }
}
```

---

### Create student experience

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `POST`                                    |
| **Path**   | `/student/experiences`                    |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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

| Field        | Type   | Required | Notes                 |
|--------------|--------|----------|-----------------------|
| title        | string | Yes      | Max 255               |
| company_name | string | Yes      | Max 255               |
| start_date   | date   | Yes      | Format: YYYY-MM-DD    |
| end_date     | date   | No       | Must be >= start_date |
| description  | string | No       |                       |

**Success (201):** `{ "message": "Experience created successfully.", "data": <experience>, "links": {...} }`

---

### Update student experience

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT` or `PATCH`                          |
| **Path**   | `/student/experiences/{id}`               |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

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

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `DELETE`                                  |
| **Path**   | `/student/experiences/{id}`               |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

**Success (200):** `{ "message": "Experience deleted successfully." }`  
**Error (404):** Experience not found or not owned by you.

[↑ Back to index](#index)

---

## Student languages

### List student languages

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/languages`                      |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

**Success (200):**

```json
{
    "data": [
        {
            "language_id": 1,
            "language_level_id": 3,
            "is_active": true,
            "language": {
                "id": 1,
                "name": "English"
            },
            "language_level": {
                "id": 3,
                "name": "Fluent"
            }
        }
    ],
    "links": {
        "self": "..."
    }
}
```

---

### Sync student languages

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `PUT`                                     |
| **Path**   | `/student/languages`                      |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Replaces all languages for the student. Send the complete list of languages.

**Request body:**

```json
{
    "languages": [
        {
            "language_id": 1,
            "language_level_id": 3,
            "is_active": true
        },
        {
            "language_id": 2,
            "language_level_id": 2
        }
    ]
}
```

| Field                         | Type    | Required | Notes                                 |
|-------------------------------|---------|----------|---------------------------------------|
| languages                     | array   | Yes      | List of language entries              |
| languages.*.language_id       | number  | Yes      | Must exist in `languages` table       |
| languages.*.language_level_id | number  | Yes      | Must exist in `language_levels` table |
| languages.*.is_active         | boolean | No       | Defaults to true                      |

**Success (200):** `{ "message": "Languages updated successfully.", "data": [...], "links": {...} }`

[↑ Back to index](#index)

---

## Student tags

### List student tags

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `GET`                                     |
| **Path**   | `/student/tags`                           |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

**Success (200):**

```json
{
    "data": [
        {
            "tag_id": 1,
            "is_active": true,
            "weight": 5,
            "tag": {
                "id": 1,
                "name": "PHP",
                "tag_type": "skill"
            }
        }
    ],
    "links": {
        "self": "..."
    }
}
```

---

### Sync student tags

|            |                             |
|------------|-----------------------------|
| **Method** | `PUT`                       |
| **Path**   | `/student/tags`             |
| **Auth**   | Bearer token + student role |

Replaces all tags/skills for the student. Send the complete list of tags. This is a **sync** operation”existing tags are
removed and replaced with the new list provided.

**Request body:**

```json
{
    "tags": [
        {
            "tag_id": 1,
            "is_active": true,
            "weight": 95
        },
        {
            "tag_id": 2,
            "is_active": true,
            "weight": 85
        },
        {
            "tag_id": 13,
            "is_active": true,
            "weight": 70
        }
    ]
}
```

| Field            | Type    | Required | Notes                                  |
|------------------|---------|----------|----------------------------------------|
| tags             | array   | Yes      | List of tag entries                    |
| tags.*.tag_id    | number  | Yes      | Must exist in `tags` table             |
| tags.*.is_active | boolean | No       | Defaults to true                       |
| tags.*.weight    | number  | No       | 0–100, represents proficiency/priority |

#### Understanding the `weight` field

The `weight` field (0–100) represents the student's **proficiency level** for that skill/tag:

| Weight Range | Proficiency Level |
|--------------|-------------------|
| 90–100       | Expert            |
| 70–89        | Advanced          |
| 50–69        | Intermediate      |
| 30–49        | Beginner          |
| 0–29         | Learning          |

This weight is used by the matching algorithm to better match students with vacancies. A higher weight indicates
stronger proficiency.

**Example – Adding skills with proficiency levels:**

```json
{
    "tags": [
        {
            "tag_id": 19,
            "is_active": true,
            "weight": 95
        },
        {
            "tag_id": 1,
            "is_active": true,
            "weight": 90
        },
        {
            "tag_id": 13,
            "is_active": true,
            "weight": 60
        },
        {
            "tag_id": 4,
            "is_active": true,
            "weight": 40
        }
    ]
}
```

In this example, the student is an expert in tag 19 (e.g., Laravel), advanced in tag 1 (e.g., PHP), intermediate in tag
13 (e.g., React), and a beginner in tag 4 (e.g., Python).

**Success (200):**

```json
{
    "message": "Tags updated successfully.",
    "data": [
        {
            "tag_id": 19,
            "is_active": true,
            "weight": 95,
            "tag": {
                "id": 19,
                "name": "Laravel",
                "tag_type": "skill"
            }
        },
        {
            "tag_id": 1,
            "is_active": true,
            "weight": 90,
            "tag": {
                "id": 1,
                "name": "PHP",
                "tag_type": "skill"
            }
        }
    ],
    "links": {
        "self": "https://<your-api-host>/api/v2/student/tags"
    }
}
```

**Error responses:**

| Status | Reason                                                       |
|--------|--------------------------------------------------------------|
| 401    | Not authenticated (missing or invalid JWT)                   |
| 403    | User is not a student                                        |
| 422    | Validation error (invalid tag_id, weight out of range, etc.) |

<details>
<summary><strong>Postman example</strong></summary>

1. **Login as a student** to get a JWT token:
   ```
   POST /api/v2/auth/login
   Content-Type: application/json
   
   { "email": "student@example.com", "password": "password123" }
   ```

2. **Sync tags** with the token:
   ```
   PUT /api/v2/student/tags
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
   GET /api/v2/student/tags
   Authorization: Bearer <your-jwt-token>
   Accept: application/json
   ```

</details>

[↑ Back to index](#index)

## Student flags (v2)

|            |                                           |
|------------|-------------------------------------------|
| **Method** | `POST`                                    |
| **Path**   | `/student/flags`                          |
| **Auth**   | `X-API-KEY` + Bearer token + student role |

Students kunnen een vacature markeren (flaggen) wanneer zij vinden dat er iets mis is met de vacature of de matchscore.
De vacature moet bestaan.

| Field           | Type    | Required | Notes                                                       |
|-----------------|---------|----------|-------------------------------------------------------------|
| vacancy_id      | integer | Yes      | Id van de vacature (`exists:vacancies,id`)                  |
| disputed_factor | string  | Yes      | Korte sleutel/omschrijving van welk onderdeel betwist wordt |
| message         | string  | No       | Optioneel toelichtend bericht van de student                |

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
    "vacancy_id": 123,
    "disputed_factor": "match_score",
    "message": "De match lijkt onjuist omdat ik niet aan de gevraagde ervaring voldoe."
}
```

</details>

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
    "message": "Vacancy flagged successfully",
    "data": {
        "id": 1,
        "student_user_id": 42,
        "vacancy_id": 123,
        "company_id": 17,
        "disputed_factor": "match_score",
        "message": "De match lijkt onjuist omdat ik niet aan de gevraagde ervaring voldoe.",
        "status": "open",
        "created_at": "...",
        "updated_at": "..."
    },
    "links": {
        "self": "https://<your-api-host>/api/v2/student/flags/1",
        "collection": "https://<your-api-host>/api/v2/student/flags"
    }
}
```

</details>

**Error (403):** Wanneer de ingelogde gebruiker geen student is:

```json
{
    "message": "Only students can flag vacancies"
}
```

**Error (422):** Validatiefouten (bijv. ontbrekende `vacancy_id` of `disputed_factor`), of
**Error (404):** Wanneer `vacancy_id` niet bestaat.

Notes

- Route: in `routes/api.php` is deze route toegevoegd:
  `Route::post('student/flags', [StudentFlagController::class, 'store']);`
- Controller: `app/Http/Controllers/Api/Student/StudentFlagController.php` bevat de implementatie en returnt een JSON
  met `message`, `data` (de aangemaakte flag) en `links` (self + collection).

[↑ Back to index](#index)

## Public data (no auth)

These endpoints return only **active** (coordinator-approved) companies and their vacancies. Use them for student/public
frontends. Companies that registered via [Register as company](#2-register-as-company-self-registration-team-a-only) do
not appear here until a stage coordinator sets their `is_active` to `true`.

### List active companies (v2)

|            |                               |
|------------|-------------------------------|
| **Method** | `GET`                         |
| **Path**   | `/companies`                  |
| **Auth**   | `X-API-KEY` required (no JWT) |

Returns only companies with `is_active: true` (coordinator-approved). No query parameters. Use for student or public
frontends.

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
            "is_active": true,
            "created_at": "...",
            "updated_at": "..."
        }
    ],
    "links": {
        "self": "..."
    }
}
```

</details>

---

### List vacancies (active companies only) (v2)

|            |                               |
|------------|-------------------------------|
| **Method** | `GET`                         |
| **Path**   | `/vacancies`                  |
| **Auth**   | `X-API-KEY` required (no JWT) |

**Query parameters:**

| Param    | Type   | Default | Description      |
|----------|--------|---------|------------------|
| per_page | number | 15      | Pagination size. |

Returns only vacancies belonging to active companies (`is_active: true`). Vacancies are loaded with `company:id,name`and
`vacancyRequirements.tag`.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "id": 1,
            "company_id": 1,
            "title": "Backend developer",
            "hours_per_week": 40,
            "status": "open",
            "company": {
                "id": 1,
                "name": "Acme Corp"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    },
    "links": {
        "self": "..."
    }
}
```

</details>

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

|            |                          |
|------------|--------------------------|
| **Method** | `GET`                    |
| **Path**   | `/coordinator/companies` |

**Query parameters:**

| Param           | Type    | Default | Description                              |
|-----------------|---------|---------|------------------------------------------|
| name            | string  | ”       | Filter by company name (partial match)   |
| industry_tag_id | number  | ”       | Filter by tag id                         |
| is_active       | boolean | ”       | Filter by active status (`true`/`false`) |
| per_page        | number  | 15      | Pagination size                          |

**Example:** `GET /coordinator/companies?name=Acme&is_active=true&per_page=10`

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
    "links": {
        "self": "..."
    }
}
```

</details>

---

### Create company

|            |                          |
|------------|--------------------------|
| **Method** | `POST`                   |
| **Path**   | `/coordinator/companies` |

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

| Field           | Type    | Required | Notes                |
|-----------------|---------|----------|----------------------|
| name            | string  | Yes      | Max 255              |
| industry_tag_id | number  | No       | Must exist in `tags` |
| email           | string  | No       | Email, max 255       |
| phone           | string  | No       | Max 50               |
| size_category   | string  | No       | Max 50               |
| photo_url       | string  | No       |                      |
| banner_url      | string  | No       | Max 512              |
| description     | string  | No       |                      |
| is_active       | boolean | No       | Defaults to `true`   |

**Success (201):** `{ "data": <company object> }`  
Use `data.id` as `company_id` when creating a company user.

---

### Get company

|            |                               |
|------------|-------------------------------|
| **Method** | `GET`                         |
| **Path**   | `/coordinator/companies/{id}` |

**Success (200):** `{ "data": <company>, "links": {...} }`  
**Error (404):** if company does not exist.

---

### Update company

|            |                               |
|------------|-------------------------------|
| **Method** | `PUT` or `PATCH`              |
| **Path**   | `/coordinator/companies/{id}` |

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

**Approving self-registered companies:** Set `is_active` to `true` to approve a company. Until then, that company and
its users/vacancies are excluded from [List active companies](#list-active-companies)
and [List vacancies](#list-vacancies-active-companies-only).

**Success (200):** `{ "data": <updated company> }`

---

### Delete company

|            |                               |
|------------|-------------------------------|
| **Method** | `DELETE`                      |
| **Path**   | `/coordinator/companies/{id}` |

**Success (204):** No content.

[↑ Back to index](#index)

---

## Users (coordinator)

Manage **student** and **company** users. For company users, the company must exist (create it first via
`/coordinator/companies`).

### List users

|            |                      |
|------------|----------------------|
| **Method** | `GET`                |
| **Path**   | `/coordinator/users` |

**Query parameters:**

| Param                 | Type    | Default | Description                                                                                                                                                     |
|-----------------------|---------|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| role                  | string  | ”       | `student` or `company` to filter (e.g. students only: `role=student`)                                                                                           |
| search                | string  | ”       | Search in first name, last name, or email (partial match)                                                                                                       |
| per_page              | number  | 15      | Pagination size                                                                                                                                                 |
| active_companies_only | boolean | false   | If `1` or `true`, only return students and company users whose company is active (useful when listing users for display). Omit to see all users for management. |
| assigned_to_me        | boolean | false   | If `1` or `true` **and** `role=student`, only return students currently assigned to the logged-in coordinator.                                                  |

**Example:** `GET /coordinator/users?role=student&search=jan&per_page=10`
**Example:** `GET /coordinator/users?role=student&per_page=10`  
**Example (only active companies’ users):** `GET /coordinator/users?active_companies_only=1`
**Example (students with a match choice):** `GET /coordinator/users?role=student&has_match_choice=1`

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
            "student_profile": {
                "user_id": 1
            }
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
            "company_user": {
                "company_id": 1,
                "job_title": "HR Manager"
            },
            "company": {
                "id": 1,
                "name": "Acme Corp"
            }
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

|            |                      |
|------------|----------------------|
| **Method** | `POST`               |
| **Path**   | `/coordinator/users` |

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

| Field       | Type   | Required            | Notes                      |
|-------------|--------|---------------------|----------------------------|
| role        | string | Yes                 | `"student"` or `"company"` |
| email       | string | Yes                 | Unique, max 255            |
| password    | string | Yes                 | Min 6                      |
| first_name  | string | Yes                 | Max 100                    |
| middle_name | string | No                  | Max 100                    |
| last_name   | string | Yes                 | Max 100                    |
| phone       | string | No                  | Max 50                     |
| company_id  | number | Yes if role=company | Must exist in `companies`  |
| job_title   | string | No                  | Max 255, for company only  |

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
    "message": "User created successfully.",
    "data": {
        <user
        object,
        same
        shape
        as
        list/show>
    }
}
```

</details>

**Errors:** 422 validation errors (e.g. duplicate email, missing `company_id` for company role).

---

### Get user

|            |                           |
|------------|---------------------------|
| **Method** | `GET`                     |
| **Path**   | `/coordinator/users/{id}` |

Returns user details. For **students**, includes all related profile data (profile, experiences, tags, languages,
preferences, favorite companies, saved vacancies).

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
                "tag": {
                    "id": 1,
                    "name": "PHP",
                    "tag_type": "skill"
                }
            }
        ],
        "student_languages": [
            {
                "language_id": 1,
                "language_level_id": 3,
                "is_active": true,
                "language": {
                    "id": 1,
                    "name": "English"
                },
                "language_level": {
                    "id": 3,
                    "name": "Fluent"
                }
            }
        ],
        "student_preferences": {
            "desired_role_tag_id": 2,
            "hours_per_week_min": 32,
            "hours_per_week_max": 40,
            "max_distance_km": 50,
            "has_drivers_license": true,
            "notes": "Prefer remote work",
            "desired_role_tag": {
                "id": 2,
                "name": "Backend Developer",
                "tag_type": "role"
            }
        },
        "student_favorite_companies": [
            {
                "company_id": 1,
                "company": {
                    "id": 1,
                    "name": "Acme Corp"
                }
            }
        ],
        "student_saved_vacancies": [
            {
                "vacancy_id": 1,
                "removed_at": null,
                "vacancy": {
                    "id": 1,
                    "title": "Backend Developer",
                    "company_id": 1
                }
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
        "company_user": {
            "company_id": 1,
            "job_title": "HR Manager"
        },
        "company": {
            "id": 1,
            "name": "Acme Corp"
        }
    }
}
```

**Error (404):** `{ "message": "User not found." }` (e.g. id is not a student/company user).

---

### Update user

|            |                           |
|------------|---------------------------|
| **Method** | `PUT` or `PATCH`          |
| **Path**   | `/coordinator/users/{id}` |

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

|            |                           |
|------------|---------------------------|
| **Method** | `DELETE`                  |
| **Path**   | `/coordinator/users/{id}` |

Only **students** can be deleted. Attempting to delete a coordinator or company user returns **403**.  
Deleting a student also **cascades** to their related data (profile, experiences, tags, languages, preferences,
favorites, saved vacancies, messages, conversations, and other student-specific records), either removing those rows or
nulling references where configured.

**Success (200):** `{ "message": "User deleted successfully." }`
**Error (403):** `{ "message": "Alleen studenten kunnen verwijderd worden" }`
**Error (404):** `{ "message": "User not found." }`

[↑ Back to index](#index)

---

## Student–coordinator assignments (coordinator)

Coordinators can manage which students are assigned to which coordinators.  
Assignments are stored in the `student_coordinator_assignments` table and are **historical**: when a student is
unassigned, the row is kept but `unassigned_at` is set.  
The `GET /coordinator/users?role=student&assigned_to_me=1` filter only considers **active** assignments (`unassigned_at`
is `null`).

### Assign student to a coordinator

Create a new assignment between a student and a coordinator. You can assign to **yourself** or to **another coordinator
**.

|            |                                               |
|------------|-----------------------------------------------|
| **Method** | `POST`                                        |
| **Path**   | `/coordinator/users/{student_id}/assignments` |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator role |

**Request body (JSON):**

```json
{
    "coordinator_user_id": 5,
    "note": "Moving this student to another coordinator."
}
```

| Field               | Type   | Required | Notes                                              |
|---------------------|--------|----------|----------------------------------------------------|
| coordinator_user_id | number | Yes      | Must exist in `users` and have role `coordinator`. |
| note                | string | No       | Optional internal note stored on the assignment.   |

**Behavior:**

- Fails with `422` if the path user is **not** a student.
- Fails with `422` if `coordinator_user_id` is not a coordinator.
- Creates a new `student_coordinator_assignments` row with:
    - `student_user_id = {student_id}`
    - `coordinator_user_id = coordinator_user_id`
    - `assigned_by_user_id = <logged-in coordinator id>`
    - `assigned_at = now`
    - `unassigned_at = null`

**Success (201):**

```json
{
    "message": "Coordinator assigned to student successfully.",
    "data": {
        "id": 1,
        "student_user_id": 10,
        "coordinator_user_id": 5,
        "assigned_by_user_id": 3,
        "assigned_at": "2025-03-10T12:00:00+00:00",
        "unassigned_at": null,
        "note": "Moving this student to another coordinator."
    }
}
```

---

### Unassign student from a coordinator

Marks the latest active assignment between a student and a coordinator as **unassigned** by setting `unassigned_at`.

|            |                                                 |
|------------|-------------------------------------------------|
| **Method** | `POST`                                          |
| **Path**   | `/coordinator/users/{student_id}/unassignments` |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator role   |

**Request body (JSON):**

```json
{
    "coordinator_user_id": 5,
    "note": "Student graduated."
}
```

| Field               | Type   | Required | Notes                                                                                           |
|---------------------|--------|----------|-------------------------------------------------------------------------------------------------|
| coordinator_user_id | number | No       | If omitted, defaults to the logged-in coordinator’s user id. Must exist in `users` if provided. |
| note                | string | No       | Optional note to update on the assignment.                                                      |

**Behavior:**

- Fails with `422` if the path user is **not** a student.
- Looks up the most recent assignment for:
    - `student_user_id = {student_id}`
    - `coordinator_user_id = coordinator_user_id` (or current coordinator if omitted)
    - `unassigned_at IS NULL`
- If no active assignment is found, returns **404**:
    - `{ "message": "Active assignment not found." }`
- Otherwise sets `unassigned_at = now` and, if provided, updates `note`.

**Success (200):**

```json
{
    "message": "Student unassigned from coordinator successfully.",
    "data": {
        "id": 1,
        "student_user_id": 10,
        "coordinator_user_id": 5,
        "assigned_by_user_id": 3,
        "assigned_at": "2025-03-10T12:00:00+00:00",
        "unassigned_at": "2025-03-11T09:00:00+00:00",
        "note": "Student graduated."
    }
}
```

[↑ Back to index](#index)

---

## Vacancies (coordinator)

Coordinators can list all vacancies across companies with optional filtering.

### List vacancies (coordinator)

|            |                                               |
|------------|-----------------------------------------------|
| **Method** | `GET`                                         |
| **Path**   | `/coordinator/vacancies`                      |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator role |

**Query parameters:**

| Param      | Type   | Default | Description                                               |
|------------|--------|---------|-----------------------------------------------------------|
| company_id | number | ”       | Filter by company id                                      |
| status     | string | ”       | Filter by vacancy status                                  |
| tag_id     | number | ”       | Filter vacancies that have this tag in their requirements |
| search     | string | ”       | Search in vacancy title (partial match)                   |
| per_page   | number | 15      | Pagination size                                           |

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
            "company": {
                "id": 1,
                "name": "Acme Corp",
                ...
            },
            "location": null,
            "vacancy_requirements": [
                {
                    "vacancy_id": 1,
                    "tag_id": 1,
                    "requirement_type": "skill",
                    "importance": null,
                    "tag": {
                        "id": 1,
                        "name": "PHP",
                        "tag_type": "skill"
                    }
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
    "links": {
        "self": "..."
    }
}
```

[↑ Back to index](#index)

---

## Student vacancies with match scores (coordinator) (v2)

|            |                                                      |
|------------|------------------------------------------------------|
| **Method** | `GET`                                                |
| **Path**   | `/coordinator/students/{user}/vacancies-with-scores` |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator role        |

List vacancies with match scores for a given student. Path parameter `user` = student user ID. **Note:** This endpoint
uses a different scoring algorithm than the student's own `GET /student/vacancies-with-scores`; response subscores here
use **skill** and **trait** categories (cosine similarity per category).

**Query parameters:**

| Param           | Type   | Default | Description                       |
|-----------------|--------|---------|-----------------------------------|
| per_page        | number | 15      | Pagination size                   |
| page            | number | 1       | Page number                       |
| industry_tag_id | number | —       | Optional. Filter by industry tag. |

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
    "data": [
        {
            "vacancy": {
                "id": 1,
                "company_id": 1,
                "title": "Backend developer",
                "company": {
                    "id": 1,
                    "name": "Acme Corp"
                }
            },
            "match_score": 78,
            "subscores": {
                "skill": {
                    "score": 0.82,
                    "explanation": "..."
                },
                "trait": {
                    "score": 0.71,
                    "explanation": "..."
                }
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    },
    "links": {
        "self": "..."
    }
}
```

</details>

**Error (404):** When the path user is not a student: `{ "message": "User is not a student." }`

[↑ Back to index](#index)

---

## Add comment to vacancy (v2)

|            |                                               |
|------------|-----------------------------------------------|
| **Method** | `POST`                                        |
| **Path**   | `/coordinator/vacancies/{vacancy}/comments`   |
| **Auth**   | `X-API-KEY` + Bearer token + coordinator role |

Add a comment to a vacancy (visible to the company). The vacancy must exist.

| Field   | Type   | Required | Notes         |
|---------|--------|----------|---------------|
| comment | string | Yes      | Comment text. |

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
    "comment": "Optional feedback for the company."
}
```

</details>

<details>
<summary><strong>Success (201) – Response body (JSON)</strong></summary>

```json
{
    "data": {
        "id": 1,
        "vacancy_id": 1,
        "author_user_id": 5,
        "comment": "Optional feedback for the company.",
        "created_at": "...",
        "updated_at": "..."
    },
    "links": {
        "self": "...",
        "collection": "..."
    }
}
```

</details>

**Error (404):** Vacancy not found.  
**Error (403):** Not a coordinator.

[↑ Back to index](#index)

---

## Match choices (coordinator) (v2)

Coordinators can list all match choices (with filters) and approve or reject them with a decision note. Only choices with status `requested` or `shortlisted` can be approved or rejected.

#### List match choices (coordinator)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/coordinator/match-choices` |
| **Auth** | `X-API-KEY` + Bearer token + coordinator role |

Returns match choices, paginated. Optional query parameters: `student_user_id`, `vacancy_id`, `company_id`, `status`, `per_page`.

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

```json
{
  "data": [
    {
      "id": 1,
      "student_user_id": 2,
      "vacancy_id": 10,
      "status": "requested",
      "student_note": "Interested in backend focus",
      "decided_by_user_id": null,
      "decided_at": null,
      "decision_note": null,
      "created_at": "2025-03-10T12:00:00+00:00",
      "updated_at": "2025-03-10T12:00:00+00:00",
      "student": { "id": 2, "email": "student@example.com", "first_name": "Jane", "last_name": "Doe" },
      "vacancy": { "id": 10, "title": "Backend developer", "company_id": 5, "company": { "id": 5, "name": "Acme Corp" } }
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 },
  "links": { "self": "https://<host>/api/v2/coordinator/match-choices" }
}
```

</details>

---

#### Approve match choice (coordinator)

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/coordinator/match-choices/{choice}/approve` |
| **Auth** | `X-API-KEY` + Bearer token + coordinator role |

Approve a match choice. The choice must have status `requested` or `shortlisted`. Path parameter `choice` is the choice ID.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "decision_note": "Approved for placement."
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| decision_note | string | Yes | Reason for the decision (non-empty). |

**Success (200):** `{ "message": "Match choice approved.", "data": <choice with student, vacancy, decided_by_user>, "links": {...} }`

**Error (403):** Choice already decided or withdrawn.

---

#### Reject match choice (coordinator)

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/coordinator/match-choices/{choice}/reject` |
| **Auth** | `X-API-KEY` + Bearer token + coordinator role |

Reject a match choice. Same status rules as approve. Path parameter `choice` is the choice ID.

<details>
<summary><strong>Request body (JSON)</strong></summary>

```json
{
  "decision_note": "Student does not meet minimum requirements for this vacancy."
}
```

</details>

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| decision_note | string | Yes | Reason for the decision (non-empty). |

**Success (200):** `{ "message": "Match choice rejected.", "data": <choice>, "links": {...} }`

**Error (403):** Choice already decided or withdrawn.

[↑ Back to index](#index)

---

## Dev / Admin (v2)

These routes do **not** require the `X-API-KEY` header; they use JWT only. Access is restricted by role (dev or admin).

### Dev API keys (v2)

|            |                                          |
|------------|------------------------------------------|
| **Method** | `GET`                                    |
| **Path**   | `/dev/api-keys`                          |
| **Auth**   | Bearer token + **dev** role (no API key) |

Returns the current dev user's active API key, if any.

<details>
<summary><strong>Success (200) – Response (with key)</strong></summary>

`{ "data": { "id", "name", "plain_key", "plain_key_preview", "is_active", "last_used_at", "created_at", "updated_at" } }`
or `{ "data": null }` if none.

</details>

|            |                                          |
|------------|------------------------------------------|
| **Method** | `POST`                                   |
| **Path**   | `/dev/api-keys`                          |
| **Auth**   | Bearer token + **dev** role (no API key) |

Create an API key for the current dev user. Body: `{ "name": "string" }` (required, max 255). If the user already has an
active key, returns that key instead of creating a new one.

<details>
<summary><strong>Success (201) – New key</strong></summary>

`{ "message": "API key generated successfully.", "data": { "id", "name", "plain_key", "plain_key_preview", "created_at" } }`

</details>

<details>
<summary><strong>Success (200) – Key already exists</strong></summary>

`{ "message": "API key already exists for this user.", "data": {...} }`

</details>

### Admin API keys (v2)

|            |                                            |
|------------|--------------------------------------------|
| **Method** | `GET`                                      |
| **Path**   | `/admin/api-keys`                          |
| **Auth**   | Bearer token + **admin** role (no API key) |

List all API keys (all users).

<details>
<summary><strong>Success (200) – Response body (JSON)</strong></summary>

`{ "data": [ { "id", "name", "plain_key_preview", "is_active", "last_used_at", "created_at", "updated_at", "user_id", "user": { "id", "email", "first_name", "last_name" } } ] }`

</details>

|            |                                            |
|------------|--------------------------------------------|
| **Method** | `DELETE`                                   |
| **Path**   | `/admin/api-keys/{apiKey}`                 |
| **Auth**   | Bearer token + **admin** role (no API key) |

Revoke an API key (sets `is_active` to false). **Success (200):** `{ "message": "API key revoked." }` **Error (404):**
API key not found.

[↑ Back to index](#index)

---

## Recommended flow for coordinators

1. **Register** → `POST /auth/register/coordinator`
2. **Login** → `POST /auth/login` → store `token`
3. **Create company** → `POST /coordinator/companies` → store `data.id`
4. **Create company user** → `POST /coordinator/users` with `role: "company"` and `company_id: <id from step 3>`
5. **Create student** → `POST /coordinator/users` with `role: "student"` (no `company_id`) → store student `data.id`
6. **Assign student to a coordinator** → `POST /coordinator/users/{student_id}/assignments` with
   `{ "coordinator_user_id": <your user id or another coordinator's> }` so the student appears in
   `GET /coordinator/users?role=student&assigned_to_me=1`.
7. **View match scores for a student** (optional) → `GET /coordinator/students/{user}/vacancies-with-scores`

Use the same Bearer token (and `X-API-KEY` header) for all requests in steps 3–7.

**Approving self-registered companies:** Companies that registered via `POST /auth/register/company` start with
`is_active: false`. To approve, use `PATCH /coordinator/companies/{id}` with `{ "is_active": true }`. Only active
companies appear in `GET /companies` and `GET /vacancies`, and only their users when using
`GET /coordinator/users?active_companies_only=1`.

[↑ Back to index](#index)

---

## Testing with Postman

Use **Base URL** `http://localhost/api/v2` (or `http://127.0.0.1:8000/api/v2` if using `php artisan serve`). Set *
*Content-Type:** `application/json` and **X-API-KEY:** `<your-api-key>` on all v2 requests. For protected routes, add *
*Authorization:** `Bearer <token>`.

### 1. Get a company user token

You need a JWT for a user with role **company** and a linked company.

**Option A – Existing company user**  
`POST /auth/login` with body:

```json
{
    "email": "company-user@example.com",
    "password": "yourpassword"
}
```

Copy `token` from the response.

**Option B – Create via coordinator**

1. `POST /auth/register/coordinator` →register coordinator.
2. `POST /auth/login` →login as coordinator, copy `token`.
3. `POST /coordinator/companies` with **Authorization: Bearer &lt;token&gt;** →create company, note `data.id`.
4. `POST /coordinator/users` with **Authorization: Bearer &lt;token&gt;** and body: `role: "company"`, `company_id` (id
   from step 3), email, password, first_name, last_name.
5. `POST /auth/login` with that company user’s email/password →copy `token`.

### 2. Test tags and vacancies

Use **Authorization: Bearer** with the company user token for all requests below.

| Step                 | Method | Path                      | Notes                                                     |
|----------------------|--------|---------------------------|-----------------------------------------------------------|
| Get my company       | GET    | `/company`                | Your company details                                      |
| Update my company    | PATCH  | `/company`                | Optional: name, email, phone, etc.                        |
| Get my profile       | GET    | `/company/profile`        | User + company_user + company                             |
| Update my profile    | PATCH  | `/company/profile`        | Optional: first_name, last_name, job_title, etc.          |
| List tags            | GET    | `/tags`                   | Optional: `?tag_type=skill`                               |
| List vacancies       | GET    | `/company/vacancies`      | Empty at first                                            |
| Create vacancy       | POST   | `/company/vacancies`      | See [Create vacancy](#create-vacancy) for body            |
| Get vacancy          | GET    | `/company/vacancies/{id}` | Use `id` from create response                             |
| Update vacancy       | PATCH  | `/company/vacancies/{id}` | Optional fields + optional `tags` to replace              |
| Delete vacancy       | DELETE | `/company/vacancies/{id}` | Returns 204                                               |
| List vacancies again | GET    | `/company/vacancies`      | Should show created/updated vacancy or fewer after delete |

**Example create vacancy body** (existing tag by id):

```json
{
    "title": "Backend developer",
    "hours_per_week": 40,
    "description": "We are looking for...",
    "status": "open",
    "tags": [
        {
            "id": 1
        }
    ]
}
```

**Example with new tags** (creates tags if they don’t exist):

```json
{
    "title": "Frontend developer",
    "tags": [
        {
            "name": "JavaScript",
            "tag_type": "skill"
        },
        {
            "name": "React",
            "tag_type": "skill"
        }
    ]
}
```

**Common issues:** 403 = not a company user or no company linked. 422 = validation (e.g. missing `title`, or tag without
`id` or without both `name` and `tag_type`).

### 3. Test as coordinator

With a coordinator JWT and **X-API-KEY** set:

| Step                 | Method | Path                                                 | Notes                                               |
|----------------------|--------|------------------------------------------------------|-----------------------------------------------------|
| List companies       | GET    | `/coordinator/companies`                             | Optional: `?name=Acme&is_active=true`               |
| List users           | GET    | `/coordinator/users`                                 | e.g. `?role=student&per_page=10` or `?role=company` |
| List vacancies       | GET    | `/coordinator/vacancies`                             | Optional: `?company_id=1&status=open`               |
| Student match scores | GET    | `/coordinator/students/{user}/vacancies-with-scores` | `{user}` = student user ID                          |
| Add comment          | POST   | `/coordinator/vacancies/{vacancy}/comments`          | Body: `{ "comment": "..." }`                        |

### 4. Test as student

With a student JWT and **X-API-KEY** set:

| Step                  | Method | Path                             | Notes                                            |
|-----------------------|--------|----------------------------------|--------------------------------------------------|
| Get profile           | GET    | `/student/profile`               | User + student_profile + experiences, tags, etc. |
| Vacancies with scores | GET    | `/student/vacancies-with-scores` | Paginated; optional `?industry_tag_id=`          |
| Top matches           | GET    | `/student/vacancies/top-matches` | Top 3 best-matching vacancies                    |

[↑ Back to index](#index)

---

## HTTP status codes

| Code | Meaning                                                          |
|------|------------------------------------------------------------------|
| 200  | OK                                                               |
| 201  | Created                                                          |
| 204  | No content (e.g. delete vacancy/company)                         |
| 401  | Unauthorized (missing or invalid JWT, or invalid/absent API key) |
| 403  | Forbidden (wrong role, e.g. not coordinator or not company)      |
| 404  | Not found (resource or user not found)                           |
| 422  | Validation error (request body in `errors` or `message`)         |

**Common error body:** 401, 403, 404 and many 422 responses return `{ "message": "..." }`. Validation errors (422) may
also include an `errors` object keyed by field.

[↑ Back to index](#index)

---

## V2 endpoints quick reference

| Method                      | Path                                                                        | Auth                             |
|-----------------------------|-----------------------------------------------------------------------------|----------------------------------|
| POST                        | `/auth/register/coordinator`                                                | None                             |
| POST                        | `/auth/register/company`                                                    | None                             |
| POST                        | `/auth/login`                                                               | None                             |
| GET                         | `/companies`                                                                | X-API-KEY                        |
| GET                         | `/vacancies`                                                                | X-API-KEY                        |
| POST                        | `/auth/logout`                                                              | X-API-KEY + Bearer               |
| POST                        | `/auth/refresh`                                                             | X-API-KEY + Bearer               |
| GET                         | `/auth/me`                                                                  | X-API-KEY + Bearer               |
| GET                         | `/student/{student}`                                                        | X-API-KEY + Bearer               |
| GET                         | `/tags`                                                                     | X-API-KEY + Bearer               |
| GET                         | `/languages`                                                                | X-API-KEY + Bearer               |
| GET                         | `/language-levels`                                                          | X-API-KEY + Bearer               |
| GET / PATCH                 | `/company`                                                                  | X-API-KEY + Bearer + company     |
| GET / PATCH                 | `/company/profile`                                                          | X-API-KEY + Bearer + company     |
| GET / POST                  | `/company/vacancies`                                                        | X-API-KEY + Bearer + company     |
| GET / PATCH / DELETE        | `/company/vacancies/{id}`                                                   | X-API-KEY + Bearer + company     |
| GET / PATCH / DELETE        | `/company/vacancies/{vacancy}/comments`, `.../comments/{comment}`           | X-API-KEY + Bearer + company     |
| GET / PATCH                 | `/student/profile`                                                          | X-API-KEY + Bearer + student     |
| GET / PATCH                 | `/student/preferences`                                                      | X-API-KEY + Bearer + student     |
| GET / POST / PATCH / DELETE | `/student/experiences`, `.../experiences/{id}`                              | X-API-KEY + Bearer + student     |
| GET / PUT                   | `/student/languages`                                                        | X-API-KEY + Bearer + student     |
| GET / POST                  | `/student/favorite-companies`, `.../favorite-companies/{companyId}`         | X-API-KEY + Bearer + student     |
| GET / PUT                   | `/student/tags`                                                             | X-API-KEY + Bearer + student     |
| GET                         | `/student/vacancies/top-matches`, `.../with-scores`, `.../{vacancy}/detail` | X-API-KEY + Bearer + student     |
| GET                         | `/student/vacancies-with-scores`                                            | X-API-KEY + Bearer + student     |
| GET / POST / DELETE         | `/student/saved-vacancies`, `.../saved-vacancies/{vacancyId}`               | X-API-KEY + Bearer + student     |
| GET / POST / PATCH / DELETE | `/coordinator/companies`, `.../companies/{id}`                              | X-API-KEY + Bearer + coordinator |
| GET / POST / PATCH / DELETE | `/coordinator/users`, `.../users/{id}`                                      | X-API-KEY + Bearer + coordinator |
| POST                        | `/coordinator/users/{student}/assignments`                                  | X-API-KEY + Bearer + coordinator |
| POST                        | `/coordinator/users/{student}/unassignments`                                | X-API-KEY + Bearer + coordinator |
| GET                         | `/coordinator/vacancies`                                                    | X-API-KEY + Bearer + coordinator |
| GET                         | `/coordinator/students/{user}/vacancies-with-scores`                        | X-API-KEY + Bearer + coordinator |
| POST                        | `/coordinator/vacancies/{vacancy}/comments`                                 | X-API-KEY + Bearer + coordinator |
| GET / POST                  | `/dev/api-keys`                                                             | Bearer + dev (no API key)        |
| GET / DELETE                | `/admin/api-keys`, `.../admin/api-keys/{apiKey}`                            | Bearer + admin (no API key)      |

[↑ Back to index](#index)

---
