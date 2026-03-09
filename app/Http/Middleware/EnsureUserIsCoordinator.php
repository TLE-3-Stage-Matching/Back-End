<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCoordinator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Coordinator) {
            return response()->json(['message' => 'Forbidden. Coordinator role required.'], 403);
        }

        return $next($request);
    }
}
