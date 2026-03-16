<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $key = ApiKey::query()
            ->where('user_id', $user?->id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->first([
                'id',
                'name',
                'plain_key',
                'plain_key_preview',
                'is_active',
                'last_used_at',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'data' => $key,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // If this dev user already has an active key, return it instead of generating a new one
        $existing = ApiKey::query()
            ->where('user_id', $user?->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'API key already exists for this user.',
                'data' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'plain_key' => $existing->plain_key,
                    'plain_key_preview' => $existing->plain_key_preview,
                    'created_at' => $existing->created_at,
                ],
            ]);
        }

        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);

        $preview = substr($plain, 0, 16);

        $apiKey = ApiKey::query()->create([
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'key_hash' => $hash,
            'plain_key' => $plain,
            'plain_key_preview' => $preview,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'API key generated successfully.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'plain_key' => $plain,
                'plain_key_preview' => $preview,
                'created_at' => $apiKey->created_at,
            ],
        ], 201);
    }
}

