<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateCompanyRequest;
use App\Http\Requests\Api\UpdateCompanyUserProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyAccountController extends Controller
{
    /**
     * Get the authenticated company user's company.
     */
    public function showCompany(Request $request): JsonResponse
    {
        $company = $request->user()->companyUser->company;

        return response()->json([
            'data' => $company,
            'links' => ['self' => url('/api/v1/company')],
        ]);
    }

    /**
     * Update the authenticated company user's company.
     */
    public function updateCompany(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $request->user()->companyUser->company;
        $company->update($request->only([
            'name', 'industry_tag_id', 'email', 'phone',
            'size_category', 'photo_url', 'is_active',
        ]));

        return response()->json([
            'data' => $company->fresh(),
            'links' => ['self' => url('/api/v1/company')],
        ]);
    }

    /**
     * Get the authenticated company user's profile (user + company_user + company).
     */
    public function showProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['companyUser.company']);

        return response()->json([
            'data' => $this->formatProfile($user),
            'links' => ['self' => url('/api/v1/company/profile')],
        ]);
    }

    /**
     * Update the authenticated company user's profile (user fields + job_title).
     */
    public function updateProfile(UpdateCompanyUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (array_key_exists('password') && $validated['password'] !== null && $validated['password'] !== '') {
            $user->password_hash = $validated['password'];
        }
        if (array_key_exists('first_name', $validated)) {
            $user->first_name = $validated['first_name'];
        }
        if (array_key_exists('middle_name', $validated)) {
            $user->middle_name = $validated['middle_name'];
        }
        if (array_key_exists('last_name', $validated)) {
            $user->last_name = $validated['last_name'];
        }
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        if (array_key_exists('job_title', $validated)) {
            $user->companyUser->update(['job_title' => $validated['job_title']]);
        }

        $user->load(['companyUser.company']);

        return response()->json([
            'data' => $this->formatProfile($user),
            'links' => ['self' => url('/api/v1/company/profile')],
        ]);
    }

    private function formatProfile(User $user): array
    {
        $data = [
            'id' => $user->id,
            'role' => $user->role->value,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'profile_photo_url' => $user->profile_photo_url,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        if ($user->relationLoaded('companyUser') && $user->companyUser) {
            $data['company_user'] = [
                'company_id' => $user->companyUser->company_id,
                'job_title' => $user->companyUser->job_title,
            ];
            if ($user->companyUser->relationLoaded('company') && $user->companyUser->company) {
                $data['company'] = $user->companyUser->company;
            }
        }

        return $data;
    }
}
