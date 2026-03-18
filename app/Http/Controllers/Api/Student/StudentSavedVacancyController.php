<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentSavedVacancyRequest;
use App\Models\StudentSavedVacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentSavedVacancyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $savedVacancies = StudentSavedVacancy::query()
            ->with('vacancy.company')
            ->where('student_user_id', $user->id)
            ->whereNull('removed_at')
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $savedVacancies->map(fn (StudentSavedVacancy $s) => $this->formatSavedVacancy($s))->all(),
            'links' => [
                'self' => url('/api/v2/student/saved-vacancies'),
            ],
        ]);
    }

    public function store(StoreStudentSavedVacancyRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $vacancyId = $request->validated()['vacancy_id'];

        $saved = StudentSavedVacancy::query()->firstOrNew([
            'student_user_id' => $user->id,
            'vacancy_id' => $vacancyId,
        ]);

        if (!$saved->exists) {
            $saved->created_at = now();
        }

        $saved->removed_at = null;
        $saved->save();
        $saved->load('vacancy.company');

        return response()->json([
            'message' => 'Vacancy saved successfully.',
            'data' => $this->formatSavedVacancy($saved),
            'links' => [
                'self' => url("/api/v2/student/saved-vacancies/{$vacancyId}"),
                'collection' => url('/api/v2/student/saved-vacancies'),
            ],
        ], 201);
    }

    private function formatSavedVacancy(StudentSavedVacancy $saved): array
    {
        $vacancy = $saved->vacancy;
        $data = [
            'student_user_id' => $saved->student_user_id,
            'vacancy_id' => $saved->vacancy_id,
            'created_at' => $saved->created_at?->toIso8601String(),
            'removed_at' => $saved->removed_at?->toIso8601String(),
            'vacancy' => $vacancy ? [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'company_id' => $vacancy->company_id,
            ] : null,
            'company' => null,
        ];
        if ($vacancy && $vacancy->relationLoaded('company') && $vacancy->company) {
            $data['company'] = [
                'id' => $vacancy->company->id,
                'name' => $vacancy->company->name,
            ];
        }
        return $data;
    }

    public function destroy(int $vacancyId)
    {
        $user = auth('api')->user();

        $updated = StudentSavedVacancy::query()
            ->where('student_user_id', $user->id)
            ->where('vacancy_id', $vacancyId)
            ->whereNull('removed_at')
            ->update([
                'removed_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'Saved vacancy not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Saved vacancy removed successfully.',
        ]);
    }
}
