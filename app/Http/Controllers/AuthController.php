<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $creds = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        // gebruikt getAuthPassword() => password_hash
        if (!$token = auth('api')->attempt($creds)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();
        if ($user->companyUser) {
            $user->load(['companyUser.company']);
        }
        if ($user->studentProfile) {
            $user->load(['studentProfile']);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => $user,
        ]);
    }

    public function me()
    {
        $user = auth('api')->user();
        if ($user && $user->companyUser) {
            $user->load(['companyUser.company']);
        }

        return response()->json(['data' => $user]);
    }

    public function logout()
    {
        auth('api')->logout(); // invalideert token (blacklist) als blacklist aan staat
        return response()->json(['message' => 'Logged out']);
    }

    public function refresh()
    {
        return response()->json([
            'token' => auth('api')->refresh(),
            'token_type' => 'Bearer',
        ]);
    }
}
