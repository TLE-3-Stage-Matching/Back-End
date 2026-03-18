<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

/**
 * Public listing of companies. Returns only active (coordinator-approved) companies.
 * Use this for student/public frontends that display companies.
 */
class PublicCompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::query()
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'data' => $companies,
            'links' => [
                'self' => url('/api/v1/companies'),
            ],
        ]);
    }
}
