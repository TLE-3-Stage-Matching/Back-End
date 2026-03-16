# Postman Testing - Exact Examples (Copy-Paste Ready)

## 1️⃣ Register as Company

**Method:** POST  
**URL:** `http://localhost:8000/api/v1/auth/register/company`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "company": {
    "name": "TechCorp Solutions",
    "industry_tag_id": null,
    "email": "company@techcorp.com",
    "phone": "+31612345678",
    "size_category": "medium",
    "description": "Leading software development company"
  },
  "user": {
    "email": "john@techcorp.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+31612345679"
  },
  "password": "password123",
  "location": {
    "address_line": "Tech Street 123",
    "postal_code": "1234AB",
    "city": "Amsterdam",
    "country": "Netherlands"
  }
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "data": { ... }
}
```

**👉 COPY THIS TOKEN!** Save it for the Authorization header.

---

## 2️⃣ Get All Available Tags (207 total)

**Method:** GET  
**URL:** `http://localhost:8000/api/v1/tags`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_FROM_STEP_1
Content-Type: application/json
```

**Response:** List of all 207 tags
```json
{
  "data": [
    {
      "id": 1,
      "name": "PHP",
      "tag_type": "skill",
      "is_active": true
    },
    {
      "id": 2,
      "name": "JavaScript",
      "tag_type": "skill",
      "is_active": true
    },
    ...
    {
      "id": 33,
      "name": "Problem Solver",
      "tag_type": "trait",
      "is_active": true
    }
  ]
}
```

---

## 3️⃣ Create Vacancy - Senior React Developer

**Method:** POST  
**URL:** `http://localhost:8000/api/v1/company/vacancies`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_FROM_STEP_1
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "location_id": 1,
  "title": "Senior React Developer",
  "hours_per_week": 40,
  "description": "We're looking for an experienced Frontend Developer to join our growing tech team. You'll work with React, TypeScript, and modern web technologies to build scalable applications.",
  "offer_text": "Competitive salary, flexible working hours, health insurance, professional development budget, home office option",
  "expectations_text": "5+ years experience with React, strong TypeScript skills, responsive design expertise, Git proficiency, problem-solving mindset",
  "tags": [
    {
      "name": "React",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 5
    },
    {
      "name": "TypeScript",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 4
    },
    {
      "name": "Tailwind CSS",
      "tag_type": "skill",
      "requirement_type": "nice_to_have",
      "importance": 3
    },
    {
      "name": "Problem Solver",
      "tag_type": "trait",
      "requirement_type": "must_have",
      "importance": 4
    },
    {
      "name": "Team Player",
      "tag_type": "trait",
      "requirement_type": "nice_to_have",
      "importance": 2
    },
    {
      "name": "Frontend Development",
      "tag_type": "industry",
      "requirement_type": "must_have",
      "importance": 5
    }
  ]
}
```

**Response:**
```json
{
  "message": "Vacancy created successfully.",
  "data": {
    "id": 12,
    "company_id": 1,
    "title": "Senior React Developer",
    "hours_per_week": 40,
    "status": "open",
    "is_active": false,
    "created_at": "2026-03-16T10:30:00Z",
    "updated_at": "2026-03-16T10:30:00Z"
  }
}
```

---

## 4️⃣ Create Vacancy - Python Backend Developer

**Method:** POST  
**URL:** `http://localhost:8000/api/v1/company/vacancies`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_FROM_STEP_1
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "location_id": 1,
  "title": "Python Backend Developer",
  "hours_per_week": 40,
  "description": "Join our backend team and build robust APIs with Python. Work with Django/Flask, PostgreSQL, and modern cloud infrastructure.",
  "offer_text": "Competitive salary, flexible hours, health insurance, home office, learning budget, tech conferences",
  "expectations_text": "3+ years Python experience, Django or Flask knowledge, REST API design, database optimization, Docker and CI/CD awareness",
  "tags": [
    {
      "name": "Python",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 5
    },
    {
      "name": "Django",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 4
    },
    {
      "name": "PostgreSQL",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 3
    },
    {
      "name": "REST API",
      "tag_type": "skill",
      "requirement_type": "must_have",
      "importance": 4
    },
    {
      "name": "Docker",
      "tag_type": "skill",
      "requirement_type": "nice_to_have",
      "importance": 3
    },
    {
      "name": "Problem Solver",
      "tag_type": "trait",
      "requirement_type": "must_have",
      "importance": 4
    },
    {
      "name": "Backend Development",
      "tag_type": "industry",
      "requirement_type": "must_have",
      "importance": 5
    }
  ]
}
```

---

## 5️⃣ View Your Vacancies

**Method:** GET  
**URL:** `http://localhost:8000/api/v1/company/vacancies`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_FROM_STEP_1
Content-Type: application/json
```

**Response:** Lists all vacancies you created with their tags

---

## 6️⃣ Testing Student Matching (As Different User)

### First: Login as Student

**Method:** POST  
**URL:** `http://localhost:8000/api/v1/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "student@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "DIFFERENT_TOKEN_FOR_STUDENT",
  "token_type": "Bearer",
  "data": { ... }
}
```

**👉 SAVE THIS STUDENT TOKEN!**

---

### View Top 3 Matches (Student)

**Method:** GET  
**URL:** `http://localhost:8000/api/v1/student/vacancies/top-matches`

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_FROM_ABOVE
Content-Type: application/json
```

**Response:**
```json
{
  "data": [
    {
      "vacancy_id": 12,
      "title": "Senior React Developer",
      "company": "TechCorp Solutions",
      "score": 94
    },
    {
      "vacancy_id": 8,
      "title": "Frontend Developer",
      "company": "WebCorp",
      "score": 87
    },
    {
      "vacancy_id": 15,
      "title": "UI Developer",
      "company": "DesignCorp",
      "score": 82
    }
  ]
}
```

---

### View All Vacancies with Detailed Scores (Student)

**Method:** GET  
**URL:** `http://localhost:8000/api/v1/student/vacancies/with-scores`

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_FROM_ABOVE
Content-Type: application/json
```

**Response:**
```json
{
  "data": [
    {
      "vacancy_id": 12,
      "title": "Senior React Developer",
      "company": "TechCorp Solutions",
      "score": 94,
      "must_have_misses": [],
      "breakdown": {
        "s_mh": 0.992,
        "s_nth": 0.876,
        "s_tags": 0.968,
        "penalty": 0.028
      }
    },
    {
      "vacancy_id": 7,
      "title": "Python Backend Developer",
      "company": "TechCorp Solutions",
      "score": 45,
      "must_have_misses": [1, 2],
      "breakdown": {
        "s_mh": 0.687,
        "s_nth": 0.45,
        "s_tags": 0.632,
        "penalty": 0.182
      }
    }
  ]
}
```

---

## 📋 Summary: Complete Flow

```
1. POST /auth/register/company
   → Get JWT token (company_token)

2. GET /tags (with company_token)
   → See all 207 available tags

3. POST /company/vacancies (with company_token)
   → Create vacancy #1 with tags

4. POST /company/vacancies (with company_token)
   → Create vacancy #2 with tags

5. GET /company/vacancies (with company_token)
   → Verify vacancies exist

6. POST /auth/login (as student, get student_token)

7. GET /student/vacancies/top-matches (with student_token)
   → See top 3 matches!

8. GET /student/vacancies/with-scores (with student_token)
   → See detailed breakdown!
```

---

## 🎯 Key Points

- **Authorization header** is required for all endpoints except registration
- **Token format:** `Authorization: Bearer YOUR_TOKEN_HERE`
- **Tag names** must exist in database (use /tags to verify)
- **requirement_type** can be: `must_have` or `nice_to_have`
- **importance** is 1-5 scale
- **is_active** defaults to `false` for vacancies
- **Matching endpoints** are student-only (use different token!)

---

**Copy-paste any of these examples directly into Postman and they'll work!** ✅


