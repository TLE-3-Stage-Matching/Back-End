# Vacancy Matching Algorithm Implementation - Complete

## Overview
A tag-based vacancy matching algorithm has been fully implemented for the Laravel project. The algorithm scores student-vacancy compatibility on a scale of 0-100 based on tag requirements and student skill weights.

## Architecture

### Files Created

#### Data Transfer Objects (DTOs) - `app/Matching/DTOs/`
1. **StudentTagDTO.php** - Represents a student's skill tag with weight
   - Properties: `tagId`, `weight` (readonly)

2. **VacancyTagDTO.php** - Represents a vacancy requirement
   - Properties: `tagId`, `requirementType` (must_have|nice_to_have), `importance` (1-5) (readonly)

3. **MatchResultDTO.php** - Result of a single vacancy match
   - Properties: `vacancyId`, `score` (0-100), `mustHaveMisses` (array), `dimensionDetail` (breakdown)

4. **CriteriaConfigDTO.php** - Configuration for the matching algorithm
   - Properties: `mustHaveWeight` (0.8), `niceToHaveWeight` (0.2), `penaltyMax` (0.25)

#### Core Services - `app/Matching/`
1. **VacancyMatchingService.php** - Pure PHP scoring engine
   - `score(array $studentTags, array $vacancyTags): MatchResultDTO` - Score a single vacancy
   - `rankForStudent(array $studentTags, array $vacanciesWithTags): MatchResultDTO[]` - Rank multiple vacancies
   - Private methods implement the algorithm steps

2. **StudentMatchDataLoader.php** - Database layer for data retrieval
   - `loadStudentTags(int $studentUserId): StudentTagDTO[]` - Load active student tags
   - `loadVacancyTags(int $vacancyId): VacancyTagDTO[]` - Load vacancy requirements
   - `loadOpenVacanciesWithTags(): array` - Batch load all open vacancies with tags (efficient)
   - `loadVacancyDetails(array $vacancyIds): array` - Load vacancy metadata (title, company)

#### Controller - `app/Http/Controllers/Api/Student/`
**StudentVacancyMatchController.php** - HTTP endpoint handler
- Constructor injects `VacancyMatchingService` and `StudentMatchDataLoader`
- `topMatches(): JsonResponse` - GET /api/v1/student/vacancies/top-matches
- `withScores(): JsonResponse` - GET /api/v1/student/vacancies/with-scores

#### Tests - `tests/Unit/Matching/`
**VacancyMatchingServiceTest.php** - Comprehensive unit test suite
- 9 test cases covering all algorithm behaviors
- All tests passing (9/9)

### Routes Added - `routes/api.php`
Two new student-only endpoints (behind `middleware('student')`):
```php
Route::get('student/vacancies/top-matches', [StudentVacancyMatchController::class, 'topMatches']);
Route::get('student/vacancies/with-scores', [StudentVacancyMatchController::class, 'withScores']);
```

## Algorithm Implementation

### Matching Formula

**Step 1: Individual Tag Match Score (m_k)**
```
If student has tag k:
    m_k = 1 + (weight_k - 3) / 20
    
    Range: w=1→m=0.90, w=2→m=0.95, w=3→m=1.00, w=4→m=1.05, w=5→m=1.10

If student does NOT have tag k:
    m_k = 0
```

**Step 2: Normalized Importance**
```
i_hat_k = importance_k / 5  (range: 0.2–1.0)
```

**Step 3: Must-Have Sub-Score (S_MH)**
```
If must_haves exist:
    S_MH = sum(i_hat_k * m_k for k in must_haves) / sum(i_hat_k for k in must_haves)
Else:
    S_MH = 1.0
```

**Step 4: Nice-To-Have Sub-Score (S_NTH)**
```
If nice_to_haves exist:
    S_NTH = sum(i_hat_k * m_k for k in nice_to_haves) / sum(i_hat_k for k in nice_to_haves)
Else:
    S_NTH = 1.0
```

**Step 5: Combined Tag Score (S_tags)**
```
S_tags = (0.8 * S_MH) + (0.2 * S_NTH)
```

**Step 6: Must-Have Penalty (P)**
```
n_miss = count of missing must-have tags
n_total = total count of must-have tags
P_max = 0.25 (constant)

P = (n_miss / max(1, n_total)) * P_max
```

**Step 7: Final Score**
```
raw = S_tags - P
score = clamp(raw * 100, 0, 100), rounded to nearest integer
```

## API Endpoints

### 1. GET /api/v1/student/vacancies/top-matches
Returns the top 3 matching vacancies for the authenticated student.

**Response:**
```json
{
  "data": [
    {
      "vacancy_id": 12,
      "title": "Junior UX Designer",
      "company": "Acme Studio",
      "score": 94
    }
  ]
}
```

### 2. GET /api/v1/student/vacancies/with-scores
Returns ALL open vacancies with scores, sorted by score descending. Includes detailed breakdown.

**Response:**
```json
{
  "data": [
    {
      "vacancy_id": 12,
      "title": "Junior UX Designer",
      "company": "Acme Studio",
      "score": 94,
      "must_have_misses": [34, 56],
      "breakdown": {
        "s_mh": 0.987,
        "s_nth": 0.761,
        "s_tags": 0.942,
        "penalty": 0.0
      }
    }
  ]
}
```

## Key Design Decisions

1. **Pure Service Layer**: VacancyMatchingService has NO dependencies on Eloquent, DB, or Laravel facades. It's 100% testable pure PHP.

2. **Data Loading Separation**: StudentMatchDataLoader handles all database queries separately, making it easy to swap or optimize.

3. **DTO-Only Interface**: All service methods work exclusively with DTOs, ensuring clean separation of concerns.

4. **Efficient Database Access**: 
   - `loadOpenVacanciesWithTags()` uses a single query + grouping to avoid N+1 problems
   - Multiple vacancies are scored in a single pass with no additional DB access

5. **Zero Composer Dependencies**: No new packages added; uses only Laravel's built-in models and relationships.

6. **Readonly DTOs**: All DTO properties are readonly (PHP 8.1+) for immutability.

7. **Type Safety**: Full strict_types=1 declaration in all files; uses typed properties and return types.

## Test Coverage

### Test Cases
1. ✅ Student with all must-have tags scores higher than missing one
2. ✅ Student with more complete skill set scores higher  
3. ✅ Student missing all must-have tags scores 0 (penalty clamps)
4. ✅ Vacancy with no must-have tags awards S_MH = 1.0
5. ✅ Vacancy with no nice-to-have tags awards S_NTH = 1.0
6. ✅ Final score is always between 0 and 100 (clamped)
7. ✅ Ranking returns results sorted by score descending
8. ✅ Must-have misses are correctly identified
9. ✅ Scores increase monotonically with student weight until clamping at 100

**Result**: 9/9 tests passing

## Integration

### No Breaking Changes
- All existing models, migrations, controllers remain unchanged
- Routes are added under existing student middleware guard
- Uses existing Eloquent relationships (StudentTag, VacancyRequirement, Vacancy, User)

### Usage Example
```php
// In any controller or service...
$service = app(VacancyMatchingService::class);
$loader = app(StudentMatchDataLoader::class);

$studentId = auth()->user()->id;
$studentTags = $loader->loadStudentTags($studentId);
$vacanciesWithTags = $loader->loadOpenVacanciesWithTags();

$results = $service->rankForStudent($studentTags, $vacanciesWithTags);
// $results is sorted by score descending
```

## Performance Notes
- Single batch query for student tags
- Single batch query for all vacancy requirements  
- Scoring is O(n*m) where n=vacancies, m=avg tags per vacancy
- No N+1 queries
- Suitable for real-time frontend requests

## Future Enhancements
1. Add caching for frequently accessed vacancy/tag combinations
2. Add filtering by location, hours, etc. before scoring
3. Store match scores in MatchVacancyScore model for historical tracking
4. Add weighting factors beyond tags (e.g., location preference, hours)
5. Machine learning integration to learn from student choices


