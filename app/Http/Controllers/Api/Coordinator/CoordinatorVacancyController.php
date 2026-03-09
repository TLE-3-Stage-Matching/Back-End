<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorVacancyController extends Controller
{
    /**
     * List all vacancies (for coordinators). Supports filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vacancy::query()
            ->with(['company', 'location', 'vacancyRequirements.tag']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('tag_id')) {
            $query->whereHas('vacancyRequirements', function ($q) use ($request) {
                $q->where('tag_id', $request->input('tag_id'));
            });
        }
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where('title', 'like', $term);
        }

        $vacancies = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $vacancies->items(),
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
            ],
            'links' => [
                'self' => url('/api/v1/coordinator/vacancies'),
            ],
        ]);
    }
}
