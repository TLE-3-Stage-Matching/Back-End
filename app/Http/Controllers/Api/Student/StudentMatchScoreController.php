<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentVacancyTagMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentMatchScoreController extends Controller
{
    public function __construct(
        protected StudentVacancyTagMatchService $matchService
    ) {}

    /**
     * List vacancies (active companies only) with match scores and subscores, sorted by score descending.
     */
    public function vacanciesWithScores(Request $request): JsonResponse
    {
        $studentUserId = (int) $request->user()->id;
        $perPage = $request->integer('per_page', 15);
        $page = max(1, $request->integer('page', 1));
        $industryTagId = $request->filled('industry_tag_id') ? $request->integer('industry_tag_id') : null;

        $paginator = $this->matchService->vacanciesWithScoresForStudent($studentUserId, $perPage, $page, $industryTagId);
        $items = collect($paginator->items())->map(fn ($row) => $this->formatVacancyWithScore($row))->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'self' => url('/api/v1/student/vacancies-with-scores'),
            ],
        ]);
    }

    /**
     * @param  array{vacancy: \App\Models\Vacancy, match_score: float, subscores: array<string, array{score: float, explanation: string}>}  $row
     * @return array<string, mixed>
     */
    protected function formatVacancyWithScore(array $row): array
    {
        $vacancy = $row['vacancy'];
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
                'company' => $vacancy->relationLoaded('company') && $vacancy->company
                    ? ['id' => $vacancy->company->id, 'name' => $vacancy->company->name]
                    : null,
            ],
            'match_score' => $row['match_score'],
            'subscores' => $row['subscores'],
        ];
    }
}
