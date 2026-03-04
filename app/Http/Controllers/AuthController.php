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

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me()
    {
        return response()->json(['data' => auth('api')->user()]);
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
