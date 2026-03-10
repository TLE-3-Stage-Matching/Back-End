<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentSavedVacancyRequest;
use App\Models\StudentSavedVacancy;
use Illuminate\Http\Request;

class StudentSavedVacancyController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $savedVacancies = StudentSavedVacancy::query()
            ->with('vacancy')
            ->where('student_user_id', $user->id)
            ->whereNull('removed_at')
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $savedVacancies,
            'links' => [
                'self' => url('/api/v1/student/saved-vacancies'),
            ],
        ]);
    }

    public function store(StoreStudentSavedVacancyRequest $request)
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

        return response()->json([
            'message' => 'Vacancy saved successfully.',
            'data' => $saved,
            'links' => [
                'self' => url("/api/v1/student/saved-vacancies/{$vacancyId}"),
                'collection' => url('/api/v1/student/saved-vacancies'),
            ],
        ], 201);
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
