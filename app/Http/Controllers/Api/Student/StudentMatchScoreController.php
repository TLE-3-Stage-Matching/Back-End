<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Matching\StudentMatchDataLoader;
use App\Matching\VacancyMatchingService;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentMatchScoreController extends Controller
{
    public function __construct(
        protected VacancyMatchingService $matchService,
        protected StudentMatchDataLoader $dataLoader,
    ) {}

    /**
     * List vacancies with scores and breakdowns (paginated), using the same
     * scoring pipeline as /student/vacancies/top-matches and /student/vacancies/with-scores.
     */
    public function vacanciesWithScores(Request $request): JsonResponse
    {
        $studentUserId = (int) $request->user()->id;
        $perPage = max(1, $request->integer('per_page', 15));
        $page = max(1, $request->integer('page', 1));
        $industryTagId = $request->filled('industry_tag_id') ? $request->integer('industry_tag_id') : null;

        $studentTags = $this->dataLoader->loadStudentTags($studentUserId);
        $vacanciesWithTags = $this->dataLoader->loadOpenVacanciesWithTags();

        if (empty($vacanciesWithTags)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
                'links' => [
                    'self' => url('/api/v1/student/vacancies-with-scores'),
                ],
            ]);
        }

        $scores = $this->matchService->rankForStudent($studentTags, $vacanciesWithTags);
        $vacancyIds = array_map(fn ($result) => $result->vacancyId, $scores);

        $vacancies = Vacancy::query()
            ->whereIn('id', $vacancyIds)
            ->with('company:id,name,industry_tag_id')
            ->get()
            ->keyBy('id');

        if ($industryTagId !== null) {
            $scores = array_values(array_filter($scores, function ($result) use ($vacancies, $industryTagId) {
                $vacancy = $vacancies->get($result->vacancyId);
                return $vacancy && $vacancy->company && (int) $vacancy->company->industry_tag_id === $industryTagId;
            }));
        }

        $total = count($scores);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($scores, $offset, $perPage);

        $items = array_values(array_filter(array_map(function ($result) use ($vacancies) {
            $vacancy = $vacancies->get($result->vacancyId);
            if (! $vacancy) {
                return null;
            }

            return [
                'vacancy' => [
                    'id' => $vacancy->id,
                    'company_id' => $vacancy->company_id,
                    'location_id' => $vacancy->location_id,
                    'title' => $vacancy->title,
                    'hours_per_week' => $vacancy->hours_per_week,
                    'description' => $vacancy->description,
                    'status' => $vacancy->status,
                    'created_at' => $vacancy->created_at?->toIso8601String(),
                    'updated_at' => $vacancy->updated_at?->toIso8601String(),
                    'company' => $vacancy->company ? [
                        'id' => $vacancy->company->id,
                        'name' => $vacancy->company->name,
                    ] : null,
                ],
                'match_score' => $result->score,
                'subscores' => [
                    'must_have' => [
                        'score' => $result->dimensionDetail['s_mh'] ?? 0.0,
                        'explanation' => 'Weighted average match for must-have tags.',
                    ],
                    'nice_to_have' => [
                        'score' => $result->dimensionDetail['s_nth'] ?? 0.0,
                        'explanation' => 'Weighted average match for nice-to-have tags.',
                    ],
                    'combined' => [
                        'score' => $result->dimensionDetail['s_tags'] ?? 0.0,
                        'explanation' => 'Combined score before penalty (0.8 * must-have + 0.2 * nice-to-have).',
                    ],
                    'penalty' => [
                        'score' => $result->dimensionDetail['penalty'] ?? 0.0,
                        'explanation' => 'Penalty for missing must-have tags.',
                    ],
                ],
            ];
        }, $slice)));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'links' => [
                'self' => url('/api/v1/student/vacancies-with-scores'),
            ],
        ]);
    }
}
