<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentFavoriteCompanyRequest;
use App\Models\StudentFavoriteCompany;
use Illuminate\Http\Request;

class StudentFavoriteCompanyController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $favorites = StudentFavoriteCompany::query()
            ->with('company')
            ->where('student_user_id', $user->id)
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $favorites,
            'links' => [
                'self' => url('/api/v1/student/favorite-companies'),
            ],
        ]);
    }

    public function store(StoreStudentFavoriteCompanyRequest $request)
    {
        $user = auth('api')->user();
        $companyId = $request->validated()['company_id'];

        $favorite = StudentFavoriteCompany::query()->firstOrCreate([
            'student_user_id' => $user->id,
            'company_id' => $companyId,
        ], [
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Company added to favorites.',
            'data' => $favorite,
            'links' => [
                'self' => url("/api/v1/student/favorite-companies/{$companyId}"),
                'collection' => url('/api/v1/student/favorite-companies'),
            ],
        ], 201);
    }

    public function destroy(int $companyId)
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
