<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;

class CoordinatorProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        if ($user->role !== UserRole::Coordinator) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        Log::debug('Coordinator profile viewed', [
            'user_id' => $user->id
        ]);

        return response()->json([
            'data' => [
                'role' => $user->role,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url
            ],
            'links' => [
                'self' => url('/api/v1/coordinator/profile')
            ]
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->role !== UserRole::Coordinator) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string'],
            'middle_name' => ['nullable', 'string'],
            'last_name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email'],
            'profile_photo_url' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6']
        ]);

        if (isset($validated['password'])) {
            $validated['password_hash'] = $validated['password'];
            unset($validated['password']);
        }

        $user->update($validated);

        Log::debug('Coordinator profile updated', [
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'Profile updated',
            'data' => [
                'role' => $user->role,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url
            ],
            'links' => [
                'self' => url('/api/v1/coordinator/profile')
            ]
        ]);
    }
}
