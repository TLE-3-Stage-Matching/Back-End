<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Company) {
            return response()->json(['message' => 'Forbidden. Company role required.'], 403);
        }

        if (! $user->companyUser) {
            return response()->json(['message' => 'Forbidden. No company linked to this user.'], 403);
        }

        return $next($request);
    }
}
