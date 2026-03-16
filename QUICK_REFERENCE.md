# Quick Reference Card - Tags & Matching Testing

## 📋 Tags Overview (207 Total)

```
┌─────────────────────────────────────────────────────┐
│           SEEDED TAGS BREAKDOWN                    │
├─────────────────────────────────────────────────────┤
│ Skills:           100+ tags                         │
│ Traits:           29 tags                           │
│ Majors:           21 tags                           │
│ Industries:       13 tags                           │
│ TOTAL:            207 tags (all active)            │
└─────────────────────────────────────────────────────┘
```

### Tag Categories You Can Use

**Skills:** React, Python, Docker, AWS, SQL, Git, etc.  
**Traits:** Leadership, Problem Solver, Team Player, Creative, etc.  
**Majors:** Software Engineering, Data Science, Cybersecurity, etc.  
**Industries:** Web Development, DevOps, Backend Development, etc.

---

## 🔐 Authentication Flow

```
COMPANY FLOW:
  1. POST /auth/register/company
     → Get JWT token
  2. Use token in Authorization header
  3. POST /company/vacancies (with tags)
  4. GET /company/vacancies (verify)

STUDENT FLOW:
  1. POST /auth/login
     → Get JWT token (different student)
  2. Use token in Authorization header
  3. GET /student/vacancies/top-matches
  4. GET /student/vacancies/with-scores
```

---

## 📝 Vacancy Creation with Tags

```json
POST /api/v1/company/vacancies

{
  "title": "Senior React Developer",
  "tags": [
    {
      "name": "React",
      "tag_type": "skill",
      "requirement_type": "must_have",    ← Important!
      "importance": 5
    },
    {
      "name": "Problem Solver",
      "tag_type": "trait",
      "requirement_type": "nice_to_have",  ← 80% vs 20%
      "importance": 4
    }
  ]
}
```

### Key Fields Explained

| Field | Values | Impact |
|-------|--------|--------|
| `name` | Any tag from DB | Must exist in database |
| `tag_type` | skill, trait, major, industry | Categorizes the requirement |
| `requirement_type` | must_have, nice_to_have | 80% vs 20% weighting |
| `importance` | 1-5 | How critical (higher = more impact) |

---

## 🎯 Matching Algorithm Quick View

```
ALGORITHM STEPS:
1. Student has tag?
   YES → m = 1 + (weight - 3) / 20
   NO  → m = 0

2. Normalize importance
   i_hat = importance / 5

3. Calculate must-have score
   S_MH = sum(i_hat × m) / sum(i_hat)

4. Calculate nice-to-have score
   S_NTH = sum(i_hat × m) / sum(i_hat)

5. Combine scores
   S_tags = (0.8 × S_MH) + (0.2 × S_NTH)

6. Apply must-have penalty
   P = (missing_must_haves / total_must_haves) × 0.25

7. Final score
   score = clamp((S_tags - P) × 100, 0, 100)

RESULT: 0-100 score per vacancy
```

---

## ✅ Postman Headers Required

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

---

## 🔗 API Endpoints Cheat Sheet

### Company (Authenticated)
```
GET    /company                      (View company info)
GET    /tags                         (View 207 available tags)
POST   /company/vacancies            (Create vacancy with tags)
GET    /company/vacancies            (List your vacancies)
GET    /company/vacancies/{id}       (View single vacancy)
PUT    /company/vacancies/{id}       (Update vacancy)
DELETE /company/vacancies/{id}       (Delete vacancy)
```

### Student (Authenticated) - Different User!
```
GET    /student/vacancies/top-matches      (Top 3 matches)
GET    /student/vacancies/with-scores      (All with scores)
```

### Auth (No Auth Required)
```
POST   /auth/register/company        (Register as company)
POST   /auth/login                   (Login any user)
POST   /auth/logout                  (Logout)
POST   /auth/refresh                 (Refresh token)
GET    /auth/me                      (Current user)
```

---

## 🎓 Example: Complete Test Scenario

### Setup
```
1. Company registers → gets JWT token
2. Company creates 2 vacancies:
   - Vacancy A: React + TypeScript (both must-have)
   - Vacancy B: Python + Django (both must-have)
```

### Testing
```
3. Student logs in → gets JWT token
4. Student has tags: React (5), Python (4)
5. Student calls /student/vacancies/with-scores
```

### Results
```
Vacancy A: Score 87  ← Has React, missing TypeScript
Vacancy B: Score 92  ← Has Python but not Django
```

---

## ❌ Common Mistakes

```
❌ Trying to create vacancy with non-existent tag name
   → Use /tags endpoint to see valid names

❌ Using company token for /student/vacancies endpoints
   → Get 403 Forbidden (student-only!)

❌ Not setting Authorization header
   → Get 401 Unauthorized

❌ Forgetting importance and requirement_type on tags
   → Endpoint may fail validation

❌ Creating vacancy with is_active=true from company
   → Defaults to false, only coordinator approves
```

---

## ✨ Pro Tips

```
💡 All tags are pre-seeded - no need to create them
💡 Use requirement_type to weight: must_have (80%) vs nice_to_have (20%)
💡 Importance 5 = critical, Importance 1 = optional
💡 Student weight 1 = weak, weight 5 = expert
💡 Missing must-have tags = big score penalty
💡 Test with different student tag profiles to see scoring differences
```

---

## 🚀 Quick Start Command Sequence

### In Postman, create 3 requests:

**Request 1: Register Company**
```
POST http://localhost:8000/api/v1/auth/register/company
(Get token from response)
```

**Request 2: Create Vacancy**
```
POST http://localhost:8000/api/v1/company/vacancies
Authorization: Bearer TOKEN
(Include tags array with requirement types)
```

**Request 3: Student Login & See Matches**
```
POST http://localhost:8000/api/v1/auth/login
(As different student - must exist in DB)

GET http://localhost:8000/api/v1/student/vacancies/with-scores
Authorization: Bearer STUDENT_TOKEN
(See matching scores!)
```

---

## 📊 Score Interpretation

```
Score 0-20:    Very poor match (missing critical skills)
Score 20-40:   Poor match (missing several requirements)
Score 40-60:   Fair match (has some requirements)
Score 60-80:   Good match (has most requirements)
Score 80-100:  Excellent match (has all/most requirements)
```

---

**Files to Review:**
- `database/seeders/TagSeeder.php` - See all 207 tags
- `POSTMAN_TESTING_GUIDE.md` - Detailed endpoint examples
- `COMPLETE_TESTING_GUIDE.md` - Full explanation
- `Postman_Collection.json` - Import ready-made collection

