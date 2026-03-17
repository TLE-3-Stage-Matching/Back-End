<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\MatchFlag;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;

class StudentFlagController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Alleen student mag flaggen
        if ($user->role !== UserRole::Student) {
            return response()->json([
                'message' => 'Only students can flag vacancies'
            ], 403);
        }

        $validated = $request->validate([
            'vacancy_id' => ['required', 'exists:vacancies,id'],
            'disputed_factor' => ['required', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        // Haal vacature op → om company_id te krijgen
        $vacancy = Vacancy::findOrFail($validated['vacancy_id']);

        $flag = MatchFlag::create([
            'student_user_id' => $user->id,
            'vacancy_id' => $vacancy->id,
            'company_id' => $vacancy->company_id, // 🔥 automatisch
            'disputed_factor' => $validated['disputed_factor'],
            'message' => $validated['message'] ?? null,
            'status' => 'open',
        ]);

        Log::debug('Vacancy flagged', [
            'student_id' => $user->id,
            'vacancy_id' => $vacancy->id
        ]);

        return response()->json([
            'message' => 'Vacancy flagged successfully',
            'data' => $flag,
            'links' => [
                'self' => url("/api/v1/student/flags/{$flag->id}"),
                'collection' => url("/api/v1/student/flags")
            ]
        ], 201);
    }
}
