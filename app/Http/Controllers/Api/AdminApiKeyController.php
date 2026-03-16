<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::query()
            ->with('user:id,email,first_name,last_name')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'user_id',
                'name',
                'plain_key_preview',
                'is_active',
                'last_used_at',
                'created_at',
                'updated_at',
            ])
            ->map(function (ApiKey $key) {
                $user = $key->user;
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'plain_key_preview' => $key->plain_key_preview,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                    'updated_at' => $key->updated_at->toIso8601String(),
                    'user_id' => $key->user_id,
                    'user' => $user ? [
                        'id' => $user->id,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $keys,
        ]);
    }

    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['is_active' => false]);

        return response()->json([
            'message' => 'API key revoked.',
        ]);
    }
}
