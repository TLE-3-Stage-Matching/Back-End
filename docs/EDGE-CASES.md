# Edge cases

This document lists known edge cases for **matching**, **validation**, and **auth**. For test coverage and examples, see [TESTS.md](TESTS.md). For API behaviour and error responses, see [API-v2.md](API-v2.md).

## Matching (VacancyMatchingService)

Behaviour is covered by [tests/Unit/Matching/VacancyMatchingServiceTest.php](../tests/Unit/Matching/VacancyMatchingServiceTest.php).

| Edge case | Behaviour |
|-----------|-----------|
| **Vacancy with no must-haves** | S_MH (must-have sub-score) is set to 1.0 so there is no penalty. |
| **Vacancy with no nice-to-haves** | S_NTH (nice-to-have sub-score) is set to 1.0. |
| **Student missing all must-haves** | The must-have penalty drives the final score to **0**. |
| **Score bounds** | The final match score is always **clamped between 0 and 100**. |
| **Must-have misses** | The API can return which required tag IDs the student is missing (`must_have_misses`) so the UI can explain gaps. |
| **Student tag weight vs vacancy importance** | Score increases with student tag weight (e.g. 1–3) and caps at 100; vacancy requirement importance (1–5) is normalized in the formula. Extra student tags (not required by the vacancy) do not reduce the score (vacancy-centric scoring). |

## Validation

### Vacancy (company)

| Rule | Error | Reference |
|------|--------|-----------|
| More than **5 major tags** on a vacancy | 422, validation error on `tags`. | [TESTS.md](TESTS.md) (StudentMatchScoreTest) |
| Requirement **importance** &gt; 5 | 422, validation error on `tags.0.importance`. | [TESTS.md](TESTS.md) |
| Importance **omitted** | Defaults to **3**. | [TESTS.md](TESTS.md) |

### Student (tags)

| Rule | Error | Reference |
|------|--------|-----------|
| Student has **more than one major** tag | 422, validation error on `tags`. | [TESTS.md](TESTS.md) |
| Tag **weight** &gt; 5 | 422, validation error on `tags.0.weight`. | [TESTS.md](TESTS.md) |
| Weight **omitted** | Defaults to **3**. | [TESTS.md](TESTS.md) |

### Student matching sandbox (skill/trait only)

| Rule | Error | Reference |
|------|--------|-----------|
| Sandbox body contains non **skill**/**trait** tag | 422, validation error on `tags`. | [TESTS.md](TESTS.md) (StudentSandboxMatchTest) |
| More than **6** skill tags | 422, validation error on `tags`. | [TESTS.md](TESTS.md) (StudentSandboxMatchTest) |
| More than **4** trait tags | 422, validation error on `tags`. | [TESTS.md](TESTS.md) |
| Empty `tags` array | Allowed; uses the student's real tags. | [TESTS.md](TESTS.md) (StudentSandboxMatchTest) |

### Student (preferences)

| Rule | Error | Reference |
|------|--------|-----------|
| **hours_per_week_max** &lt; **hours_per_week_min** | 422, validation error on `hours_per_week_max`. | [TESTS.md](TESTS.md) (StudentProfileTest) |

### Experience (student)

| Rule | Error | Reference |
|------|--------|-----------|
| **end_date** before **start_date** | 422, validation error on `end_date`. | [TESTS.md](TESTS.md) |

## Auth & authorization

| Case | Response | Reference |
|------|----------|-----------|
| **Duplicate email** on profile update | 422, validation error on `email`. | [TESTS.md](TESTS.md) (StudentProfileTest) |
| **Missing or invalid JWT** (protected route) | 401 Unauthorized. | [API-v2.md](API-v2.md) |
| **Missing or invalid API key** (v2) | 401. | [API-v2.md](API-v2.md) |
| **Wrong role** (e.g. coordinator calling student-only endpoint) | 403, e.g. “Forbidden. Student role required.” | [TESTS.md](TESTS.md), [API-v2.md](API-v2.md) |
| **Student accessing another student’s resource** (e.g. experience) | 404. | [TESTS.md](TESTS.md) |

## See also

- [TESTS.md](TESTS.md) — Full test catalog and how each edge case is asserted.
- [API-v2.md](API-v2.md) — HTTP status codes, validation messages, and role requirements.
