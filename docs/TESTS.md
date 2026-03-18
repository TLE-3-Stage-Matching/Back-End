# Test Documentation

This document catalogs all PHPUnit tests in the project: what each test does, how it works, and which application code it covers. Use it for onboarding, code reviews, and identifying coverage gaps.

## 1. Overview

- **Framework:** PHPUnit 11, via Laravel's test runner (`php artisan test`).
- **Suites:** Two suites defined in [phpunit.xml](phpunit.xml):
  - **Unit** — `tests/Unit`: isolated logic (services, helpers), no HTTP, minimal DB where needed.
  - **Feature** — `tests/Feature`: HTTP requests, auth, DB (often `RefreshDatabase`), full stack.
- **Base:** Feature tests extend [tests/TestCase.php](tests/TestCase.php) (Laravel base); some unit tests extend PHPUnit's `TestCase` directly (e.g. [VacancyMatchingServiceTest](tests/Unit/Matching/VacancyMatchingServiceTest.php)).

---

## 2. How to run tests

| Command | Description |
|--------|-------------|
| `composer test` or `php artisan test` | Run the full test suite. |
| `php artisan test --testsuite=Unit` | Run only Unit tests. |
| `php artisan test --testsuite=Feature` | Run only Feature tests. |
| `php artisan test tests/Unit/StudentVacancyTagMatchServiceTest.php` | Run a single test file. |
| `php artisan test --filter=test_student_can_get_own_profile` | Run tests whose name matches the filter. |

**Coverage (optional):** If you have Xdebug or PCOV enabled, you can generate an HTML coverage report, for example:

```bash
php artisan test --coverage
# or
./vendor/bin/phpunit --coverage-html build/coverage
```

---

## 3. Unit tests

### 3.1 ExampleTest

**File:** [tests/Unit/ExampleTest.php](tests/Unit/ExampleTest.php)

Placeholder test to verify the unit suite runs.

| Test | What | How |
|-----|------|-----|
| `test_that_true_is_true` | Asserts that a basic assertion passes. | No setup. Asserts `true === true`. |

---

### 3.2 StudentVacancyTagMatchServiceTest

**File:** [tests/Unit/StudentVacancyTagMatchServiceTest.php](tests/Unit/StudentVacancyTagMatchServiceTest.php)  
**Class under test:** [App\Services\StudentVacancyTagMatchService](app/Services/StudentVacancyTagMatchService.php)

Tests student–vacancy tag-based matching: cosine scoring, overall score, subscores, and vacancy listing (major/industry/active company).

| Test | What | How |
|-----|------|-----|
| `test_cosine_score_same_vectors_returns_100` | Same vector compared with itself yields score 100. | Builds identical tag-weight vectors; calls `cosineScore($vec, $vec)`; asserts result is 100.0. |
| `test_cosine_score_no_overlap_returns_0` | Vectors with no shared tag IDs yield 0. | Two vectors with disjoint tag IDs; `cosineScore`; asserts 0.0. |
| `test_cosine_score_empty_vector_returns_0` | Any empty vector argument yields 0. | Calls with one or both vectors empty; asserts 0.0 each time. |
| `test_score_high_when_student_and_vacancy_share_tags` | Student and vacancy sharing a tag produce a high score (≥99). | Creates student with one skill tag, vacancy with same tag (importance 10); `score($studentId, $vacancyId)`; asserts score ≥ 99. |
| `test_extra_student_tags_do_not_lower_score` | Extra student tags (not required by vacancy) do not reduce the score (vacancy-centric). | Student has matching tag + extra tag; vacancy requires only the matching tag; asserts score ≥ 99. |
| `test_score_zero_when_no_tag_overlap` | No shared tags between student and vacancy yields 0. | Student has tag A, vacancy requires tag B; `score`; asserts 0.0. |
| `test_score_with_subscores_returns_overall_and_categories` | `scoreWithSubscores` returns overall score and subscores with keys `skill`, `trait` and `score`/`explanation`. | One shared skill tag; calls `scoreWithSubscores`; asserts keys `overall`, `subscores`, and subscores structure; overall ≥ 99. |
| `test_overall_score_uses_only_skill_and_trait_major_industry_do_not_affect_score` | Overall score uses only skill and trait; major/industry do not change it. | Student and vacancy with same skill + major + industry; compare `score` with vacancy that has only the skill; asserts scores equal and ≥ 99. |
| `test_when_student_has_major_only_vacancies_with_that_major_are_returned` | Vacancy list for student with a major only includes vacancies requiring that major. | Student with one major tag; two vacancies (one with that major, one with another); `vacanciesWithScoresForStudent`; asserts total 1 and correct title. |
| `test_when_student_has_no_major_all_active_vacancies_returned` | Student with no major sees all active vacancies. | Student with no tags; two vacancies; `vacanciesWithScoresForStudent`; asserts total 2. |
| `test_when_industry_tag_id_passed_only_vacancies_from_companies_with_that_industry` | Filtering by `industry_tag_id` returns only vacancies from companies with that industry. | Two companies (different industry_tag_id), one vacancy each; call with one industry id; asserts total 1 and correct vacancy title. |
| `test_vacancies_with_scores_only_includes_active_companies` | Only vacancies belonging to active companies are included. | Active and inactive company, one vacancy each; `vacanciesWithScoresForStudent`; asserts total 1 and "Open role". |

Uses `RefreshDatabase`; creates `User`, `StudentProfile`, `StudentTag`, `Company`, `Vacancy`, `VacancyRequirement`, `Tag` as needed.

---

### 3.3 VacancyMatchingServiceTest (Matching)

**File:** [tests/Unit/Matching/VacancyMatchingServiceTest.php](tests/Unit/Matching/VacancyMatchingServiceTest.php)  
**Class under test:** [App\Matching\VacancyMatchingService](app/Matching/VacancyMatchingService.php) and DTOs ([StudentTagDTO](app/Matching/DTOs/StudentTagDTO.php), [VacancyTagDTO](app/Matching/DTOs/VacancyTagDTO.php))

Tests the low-level matching algorithm: must-have vs nice-to-have, penalty when must-haves are missing, edge cases for S_MH/S_NTH, score clamping, ranking, and must-have misses.

| Test | What | How |
|-----|------|-----|
| `test_student_with_all_must_haves_scores_higher_than_missing_one` | Student with all must-have tags scores higher than one missing a must-have. | Two must-have vacancy tags; two student tag sets (both tags vs one); `score` for each; asserts score with both > score missing one. |
| `test_complete_skill_set_scores_higher` | More complete skill set yields a higher score. | Same as above (complete vs partial); asserts complete > partial. |
| `test_missing_all_must_haves_scores_zero` | Student with only nice-to-have tags (missing all must-haves) gets 0 due to penalty. | Vacancy with must-have and nice-to-have; student has only nice-to-have tags; `score`; asserts 0. |
| `test_vacancy_with_no_must_haves_awards_s_mh_1` | Vacancy with no must-haves gives S_MH = 1.0. | Vacancy tags all nice_to_have; student has both; get `dimensionDetail['s_mh']`; asserts 1.0. |
| `test_vacancy_with_no_nice_to_haves_awards_s_nth_1` | Vacancy with no nice-to-haves gives S_NTH = 1.0. | Vacancy tags all must_have; student has both; get `dimensionDetail['s_nth']`; asserts 1.0. |
| `test_final_score_clamped_0_to_100` | Final score is always between 0 and 100. | Vacancy and student with matching tags; `score`; asserts score ≥ 0 and ≤ 100. |
| `test_rank_for_student_returns_sorted_results` | Ranking returns results sorted by score descending. | Student tags; two vacancy tag sets; `rankForStudent`; asserts count 2 and first score ≥ second. |
| `test_must_have_misses_are_tracked` | Must-have tag IDs the student is missing are recorded. | Vacancy with three must-haves; student with one; `score`; asserts `mustHaveMisses` count 2 and contains expected tag IDs. |
| `test_scores_increase_with_weight` | Score increases with student tag weight and clamps at 100 (92 → 96 → 100 for weights 1–3). | Single must-have vacancy tag; students with weight 1, 2, 3; asserts exact scores 92, 96, 100 and strict progression. |

No database; uses in-memory DTOs only.

---

## 4. Feature tests

### 4.1 ExampleTest

**File:** [tests/Feature/ExampleTest.php](tests/Feature/ExampleTest.php)

Smoke test for the application root.

| Test | What | How |
|-----|------|-----|
| `test_the_application_returns_a_successful_response` | Root URL returns HTTP 200. | GET `/`; assert status 200. |

---

### 4.2 StudentProfileTest

**File:** [tests/Feature/StudentProfileTest.php](tests/Feature/StudentProfileTest.php)  
**Covers:** Student profile API — [StudentProfileController](app/Http/Controllers/Api/Student/) (and related: preferences, experiences, languages, tags).

Uses `RefreshDatabase`. `setUp` creates a student user, empty `StudentProfile`, and JWT token.

#### GET /student/profile

| Test | What | How |
|-----|------|-----|
| `test_student_can_get_own_profile` | Authenticated student can fetch own profile with expected structure. | GET `/api/v1/student/profile` with Bearer token; assert 200, JSON structure (data.role, student_profile, experiences, tags, languages, preferences), and role `student`. |
| `test_non_student_cannot_access_student_profile` | Coordinator (or other non-student) receives 403. | GET with coordinator JWT; assert 403 and message "Forbidden. Student role required." |
| `test_unauthenticated_user_cannot_access_student_profile` | No token yields 401. | GET without Authorization; assert 401. |

#### PUT/PATCH /student/profile

| Test | What | How |
|-----|------|-----|
| `test_student_can_update_user_fields` | Student can update first_name, last_name, phone. | PATCH with new values; assert 200, JSON paths, and DB has updated user row. |
| `test_student_can_update_profile_fields` | Student can update headline, bio, city, country, searching_status. | PATCH profile fields; assert 200, JSON paths, and `student_profiles` row updated. |
| `test_student_can_update_email` | Student can change email. | PATCH email; assert 200 and DB has new email. |
| `test_student_cannot_use_duplicate_email` | Duplicate email returns 422. | Create another user with email; PATCH same email; assert 422 and validation error on `email`. |
| `test_student_can_update_password` | Student can set new password and log in with it. | PATCH password; assert 200; POST login with new password; assert 200 and token. |
| `test_student_can_update_privacy_settings` | Student can set exclude_demographics and exclude_location. | PATCH both; assert 200 and JSON paths true. |

#### Student preferences

| Test | What | How |
|-----|------|-----|
| `test_student_can_get_preferences` | Student can fetch preferences with desired_role_tag, hours, etc. | Create preference; GET `/api/v1/student/preferences`; assert 200 and JSON structure. |
| `test_student_can_update_preferences` | Student can update role, hours, distance, drivers license, notes. | PATCH with tag, hours, distance, has_drivers_license, notes; assert 200, JSON paths, and DB has `student_preferences` row. |
| `test_preferences_validation_max_must_be_gte_min` | hours_per_week_max < min returns 422. | PATCH with min 40, max 20; assert 422 and validation error on `hours_per_week_max`. |

#### Student experiences CRUD

| Test | What | How |
|-----|------|-----|
| `test_student_can_list_experiences` | Student sees own experiences. | Create one experience; GET `/api/v1/student/experiences`; assert 200 and one item with title. |
| `test_student_can_create_experience` | Student can add experience. | POST with title, company, dates, description; assert 201, JSON paths, and DB has row. |
| `test_experience_end_date_must_be_after_start_date` | end_date before start_date returns 422. | POST with start after end; assert 422 and error on `end_date`. |
| `test_student_can_update_own_experience` | Student can update own experience. | Create experience; PATCH with new title/description; assert 200 and JSON. |
| `test_student_cannot_update_other_students_experience` | Updating another student's experience returns 404. | Create experience for other student; PATCH as first student; assert 404. |
| `test_student_can_delete_own_experience` | Student can delete own experience. | Create experience; DELETE; assert 200 and message; assert DB missing row. |
| `test_student_cannot_delete_other_students_experience` | Deleting another's experience returns 404; row remains. | Other student's experience; DELETE as first student; assert 404 and DB still has row. |

#### Student languages sync

| Test | What | How |
|-----|------|-----|
| `test_student_can_list_languages` | Student can list language entries. | Create Language, Level, StudentLanguage; GET `/api/v1/student/languages`; assert 200 and one item. |
| `test_student_can_sync_languages` | PUT replaces with given set (two languages). | PUT with two language+level entries; assert 200 and count 2; assert DB count 2. |
| `test_sync_languages_replaces_existing` | Sync replaces previous list (e.g. only German after English+Dutch). | Create two StudentLanguage rows; PUT with one language; assert 200, count 1, correct name; assert DB count 1. |

#### Student tags sync

| Test | What | How |
|-----|------|-----|
| `test_student_can_list_tags` | Student can list own tags with weight. | Create Tag and StudentTag; GET `/api/v1/student/tags`; assert 200, one item, tag name and weight. |
| `test_student_can_sync_tags` | PUT replaces with given set (e.g. three skills). | PUT with three tag ids and weights; assert 200 and count 3; assert DB rows and weight. |
| `test_sync_tags_replaces_existing` | Sync replaces previous tags (e.g. only Python after PHP). | Create one StudentTag; PUT with one different tag; assert 200, one item, and DB missing old tag. |

#### Full profile workflow

| Test | What | How |
|-----|------|-----|
| `test_full_profile_update_workflow` | Full flow: profile → preferences → experience → languages → tags; GET profile reflects all. | Sequential PATCH/POST/PUT for profile, preferences, experience, languages, tags; GET profile; assert 200 and all sections populated (name, headline, hours, one experience, one language, one tag). |

---

### 4.3 StudentMatchScoreTest

**File:** [tests/Feature/StudentMatchScoreTest.php](tests/Feature/StudentMatchScoreTest.php)  
**Covers:** Student vacancy match API (v2): [StudentVacancyMatchController](app/Http/Controllers/Api/Student/StudentVacancyMatchController.php), coordinator student vacancies, and validation for vacancy/student tags.

Uses `RefreshDatabase`. Helper `makeApiKey()` creates an active API key for v2 endpoints that require `X-API-KEY`.

#### Student vacancy detail and list with scores

| Test | What | How |
|-----|------|-----|
| `test_student_vacancy_detail_includes_match_result` | GET vacancy detail returns vacancy plus match_result (match_score, subscores). | Student + profile, company, vacancy; GET `/api/v2/student/vacancies/{id}/detail` with API key + Bearer; assert 200 and structure (vacancy, match_result with match_score and subscores must_have, nice_to_have, combined, penalty). |
| `test_student_can_get_vacancies_with_scores` | Student can list vacancies with match scores and pagination. | Student, company, vacancy; GET `/api/v2/student/vacancies-with-scores` with API key + Bearer; assert 200, data array with vacancy + match_score + subscores, meta (current_page, etc.), links. |
| `test_non_student_cannot_access_vacancies_with_scores` | Non-student gets 403. | Coordinator token; GET vacancies-with-scores; assert 403 and message. |
| `test_unauthenticated_user_cannot_access_vacancies_with_scores` | No auth yields 401. | API key only; GET vacancies-with-scores; assert 401. |

#### Coordinator student vacancies

| Test | What | How |
|-----|------|-----|
| `test_coordinator_can_get_student_vacancies_with_scores` | Coordinator can fetch vacancies-with-scores for a given student. | Coordinator + student + vacancy; GET `/api/v2/coordinator/students/{id}/vacancies-with-scores` with API key + coordinator token; assert 200 and data/meta/links. |
| `test_coordinator_gets_404_when_user_is_not_student` | Coordinator requesting a company user gets 404. | Company user id; GET coordinator students/{id}/vacancies-with-scores; assert 404 and message "User is not a student." |
| `test_student_cannot_access_coordinator_student_vacancies_endpoint` | Student cannot access another student via coordinator endpoint. | Student A token; GET coordinator students/{B}/vacancies-with-scores; assert 403. |

#### Vacancy and student tag validation

| Test | What | How |
|-----|------|-----|
| `test_vacancy_create_with_six_major_tags_returns_422` | Vacancy with more than five major tags fails validation. | Company user; six major tags; POST `/api/v1/company/vacancies` with tags; assert 422 and validation errors on `tags`. |
| `test_vacancy_create_with_importance_above_five_returns_422` | Importance > 5 fails. | Company user; POST vacancy with tag importance 6; assert 422 and error on `tags.0.importance`. |
| `test_vacancy_create_without_importance_defaults_to_three` | Omitting importance defaults to 3. | POST vacancy with tag without importance; assert 201; assert DB vacancy_requirements has importance 3. |
| `test_student_sync_tags_with_two_majors_returns_422` | Student cannot have two majors. | Student; two major tags; PUT `/api/v1/student/tags`; assert 422 and error on `tags`. |
| `test_student_sync_tags_weight_above_five_returns_422` | Weight > 5 fails. | Student; PUT tags with weight 6; assert 422 and error on `tags.0.weight`. |
| `test_student_sync_tags_without_weight_defaults_to_three` | Omitting weight defaults to 3. | PUT tags without weight; assert 200; assert DB student_tags has weight 3. |

#### Filtering

| Test | What | How |
|-----|------|-----|
| `test_vacancies_with_scores_industry_tag_id_filters_by_company_industry` | `industry_tag_id` query filters by company industry. | Two industries, two companies (one vacancy each); student; GET vacancies-with-scores with one industry_tag_id; assert 200, one item, correct vacancy title and subscores present. |

---

### 4.4 CoordinatorTagApiTest

**File:** [tests/Feature/CoordinatorTagApiTest.php](tests/Feature/CoordinatorTagApiTest.php)  
**Covers:** [CoordinatorTagController](app/Http/Controllers/Api/Coordinator/CoordinatorTagController.php) — coordinator tags CRUD and authorization.

Uses `RefreshDatabase`. `setUp` creates a coordinator user and JWT; `apiAsCoordinator()` sends requests with that token.

| Test | What | How |
|-----|------|-----|
| `test_coordinator_can_list_tags` | Coordinator can list tags with pagination. | Create two tags (one inactive); GET `/api/v1/coordinator/tags?per_page=10`; assert 200, meta.total 2, data count 2. |
| `test_coordinator_can_create_tag` | Coordinator can create a tag (name, tag_type, is_active default true). | POST with name and tag_type; assert 201, JSON paths; assert DB has tag. |
| `test_coordinator_cannot_create_duplicate_name_and_type_tag` | Duplicate name+tag_type returns 422. | Create tag; POST same name+type; assert 422 and validation error on `name`. |
| `test_coordinator_can_update_tag` | Coordinator can update name and is_active. | Create tag; PATCH with new name and is_active false; assert 200 and DB updated. |
| `test_coordinator_can_delete_unused_tag` | Unused tag can be deleted (204). | Create tag; DELETE; assert 204 and DB missing tag. |
| `test_coordinator_cannot_delete_tag_that_is_in_use` | Tag in use (e.g. StudentTag) cannot be deleted; 422 and message. | Create tag and StudentTag; DELETE; assert 422 and message about deactivating instead; assert DB still has tag. |
| `test_non_coordinator_cannot_manage_tags` | Student cannot create tag; 403. | Student token; POST tag; assert 403 and "Coordinator role required." |
| `test_unauthenticated_user_cannot_manage_tags` | No auth yields 401. | POST without token; assert 401. |

---

## 5. Coverage map

| Test file (or group) | Application code covered |
|----------------------|---------------------------|
| [StudentProfileTest](tests/Feature/StudentProfileTest.php) | Student profile, preferences, experiences, languages, tags (student API controllers and related models). |
| [StudentMatchScoreTest](tests/Feature/StudentMatchScoreTest.php) | Student vacancy match (v2) endpoints, coordinator student vacancies endpoint, vacancy create validation (company), student tag sync validation. |
| [CoordinatorTagApiTest](tests/Feature/CoordinatorTagApiTest.php) | Coordinator tags API (list, create, update, delete; in-use check). |
| [StudentVacancyTagMatchServiceTest](tests/Unit/StudentVacancyTagMatchServiceTest.php) | [StudentVacancyTagMatchService](app/Services/StudentVacancyTagMatchService.php): scoring and vacancy listing for students. |
| [VacancyMatchingServiceTest](tests/Unit/Matching/VacancyMatchingServiceTest.php) | [VacancyMatchingService](app/Matching/VacancyMatchingService.php) and matching DTOs. |
| [ExampleTest](tests/Unit/ExampleTest.php), [ExampleTest](tests/Feature/ExampleTest.php) | No specific app code; placeholders/smoke. |

**Not covered (or only partially) by the above:** Company account/profile, company vacancies (beyond one validation test), company match choices, Admin API (e.g. API keys), Coordinator flags, Coordinator match choices, Auth (login/register) beyond one login assertion in StudentProfileTest, other v1/v2 endpoints not listed.

---

## 6. Maintaining this doc

- When you **add, remove, or rename** test methods or test files, update this document so the catalog and coverage map stay accurate.
- Optionally, run `php artisan test --list-tests` or grep for `public function test_` to spot new tests and remind yourself to document them.
- A CI step that compares test count or file list to this doc is out of scope for the initial version but can be added later.
