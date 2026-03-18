<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentFavoriteCompanyRequest;
use App\Models\StudentFavoriteCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFavoriteCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $favorites = StudentFavoriteCompany::query()
            ->with('company.industryTag')
            ->where('student_user_id', $user->id)
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $favorites->map(fn (StudentFavoriteCompany $fc) => [
                'company_id' => $fc->company_id,
                'created_at' => $fc->created_at?->toIso8601String(),
                'company' => $fc->relationLoaded('company') && $fc->company ? [
                    'id' => $fc->company->id,
                    'name' => $fc->company->name,
                    'industry_tag_id' => $fc->company->industry_tag_id,
                    'industry_tag' => $fc->company->relationLoaded('industryTag') && $fc->company->industryTag ? [
                        'id' => $fc->company->industryTag->id,
                        'name' => $fc->company->industryTag->name,
                        'tag_type' => $fc->company->industryTag->tag_type,
                    ] : null,
                    'email' => $fc->company->email,
                    'phone' => $fc->company->phone,
                    'size_category' => $fc->company->size_category,
                    'photo_url' => $fc->company->photo_url,
                    'banner_url' => $fc->company->banner_url,
                    'description' => $fc->company->description,
                    'is_active' => $fc->company->is_active,
                    'created_at' => $fc->company->created_at?->toIso8601String(),
                    'updated_at' => $fc->company->updated_at?->toIso8601String(),
                ] : null,
            ])->values()->all(),
            'links' => [
                'self' => url('/api/v1/student/favorite-companies'),
            ],
        ]);
    }

    public function store(StoreStudentFavoriteCompanyRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $companyId = $request->validated()['company_id'];

        $favorite = StudentFavoriteCompany::query()->firstOrCreate([
            'student_user_id' => $user->id,
            'company_id' => $companyId,
        ], [
            'created_at' => now(),
        ]);

        $favorite->load('company.industryTag');

        return response()->json([
            'message' => 'Company added to favorites.',
            'data' => [
                'company_id' => $favorite->company_id,
                'created_at' => $favorite->created_at?->toIso8601String(),
                'company' => $favorite->relationLoaded('company') && $favorite->company ? [
                    'id' => $favorite->company->id,
                    'name' => $favorite->company->name,
                    'industry_tag_id' => $favorite->company->industry_tag_id,
                    'industry_tag' => $favorite->company->relationLoaded('industryTag') && $favorite->company->industryTag ? [
                        'id' => $favorite->company->industryTag->id,
                        'name' => $favorite->company->industryTag->name,
                        'tag_type' => $favorite->company->industryTag->tag_type,
                    ] : null,
                    'email' => $favorite->company->email,
                    'phone' => $favorite->company->phone,
                    'size_category' => $favorite->company->size_category,
                    'photo_url' => $favorite->company->photo_url,
                    'banner_url' => $favorite->company->banner_url,
                    'description' => $favorite->company->description,
                    'is_active' => $favorite->company->is_active,
                    'created_at' => $favorite->company->created_at?->toIso8601String(),
                    'updated_at' => $favorite->company->updated_at?->toIso8601String(),
                ] : null,
            ],
            'links' => [
                'self' => url("/api/v1/student/favorite-companies/{$companyId}"),
                'collection' => url('/api/v1/student/favorite-companies'),
            ],
        ], 201);
    }

    public function destroy(int $companyId): JsonResponse
    {
        $user = auth('api')->user();

        $deleted = StudentFavoriteCompany::query()
            ->where('student_user_id', $user->id)
            ->where('company_id', $companyId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Favorite company not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Company removed from favorites.',
        ]);
    }
}
