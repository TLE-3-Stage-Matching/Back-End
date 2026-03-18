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

        if (! $user || ! in_array($user->role, [UserRole::Coordinator, UserRole::Admin], true)) {
            return response()->json(['message' => 'Forbidden. Coordinator or admin role required.'], 403);
        }

        return $next($request);
    }
}
