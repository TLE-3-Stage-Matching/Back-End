<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-API-KEY') ?? $request->query('api_key');

        if (! $provided) {
            return response()->json(['message' => 'Missing API key.'], 401);
        }

        $hash = hash('sha256', $provided);

        $apiKey = ApiKey::query()
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid or inactive API key.'], 401);
        }

        if (! $apiKey->last_used_at) {
            $apiKey->forceFill(['last_used_at' => now()])->save();
        } else {
            $apiKey->timestamps = false;
            $apiKey->forceFill(['last_used_at' => now()])->save();
            $apiKey->timestamps = true;
        }

        return $next($request);
    }
}

