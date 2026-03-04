<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;

class CoordinatorRegisterController extends Controller
{
    public function registerCoordinator(Request $request): JsonResponse
    {
        Log::debug('Coordinator register attempt', $request->except('password'));

        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
        ]);

        $user = User::create([
            'role' => UserRole::Coordinator,
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
        ]);

        return response()->json([
            'message' => 'Coordinator account succesvol aangemaakt',
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'links' => [
                'self' => url('/api/register/coordinator')
            ]
        ], 201);
    }
}
