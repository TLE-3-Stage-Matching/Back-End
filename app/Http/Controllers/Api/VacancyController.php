<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public listing of vacancies. Returns only vacancies from active (coordinator-approved) companies.
 * Use this for student/public frontends that display vacancies.
 */
class VacancyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vacancy::query()
            ->whereHas('company', fn ($q) => $q->active());

        $perPage = $request->integer('per_page', 15);
        $vacancies = $query->with('company:id,name')->latest()->paginate($perPage);

        $items = $vacancies->getCollection();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
            ],
            'links' => [
                'self' => url('/api/v1/vacancies'),
            ],
        ]);
    }
}
