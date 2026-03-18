<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StudentProfileViewController extends Controller
{
    public function show(User $student): JsonResponse
    {
        $viewer = auth()->user();

        // Alleen company en coordinator mogen student bekijken
        if (!in_array($viewer->role, [UserRole::Company, UserRole::Coordinator])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($student->role !== UserRole::Student) {
            return response()->json([
                'message' => 'User is not a student'
            ], 404);
        }

        $student->load([
            'studentProfile',
            'studentExperiences',
            'studentTags.tag',
            'studentLanguages.language',
            'studentLanguages.languageLevel',
            'studentPreferences.desiredRoleTag',
        ]);

        Log::debug('Student profile viewed', [
            'viewer_id' => $viewer->id,
            'student_id' => $student->id
        ]);

        return response()->json([
            'data' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'profile_photo_url' => $student->profile_photo_url,
                'student_profile' => $student->studentProfile,
                'experiences' => $student->studentExperiences,
                'tags' => $student->studentTags,
                'languages' => $student->studentLanguages,
                'preferences' => $student->studentPreferences
            ],
            'links' => [
                'self' => url("/api/v1/students/{$student->id}")
            ]
        ]);
    }
}
