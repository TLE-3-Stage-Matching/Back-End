<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterCompanyRequest;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CompanyRegistrationController extends Controller
{
    public function store(RegisterCompanyRequest $request)
    {
        $data = $request->validated();

        $out = DB::transaction(function () use ($data) {
            // 1) Create company
            $company = Company::create([
                'name' => $data['company']['name'],
                'industry_tag_id' => $data['company']['industry_tag_id'] ?? null,
                'email' => $data['company']['email'] ?? null,
                'phone' => $data['company']['phone'] ?? null,
                'size_category' => $data['company']['size_category'] ?? null,
                'photo_url' => $data['company']['photo_url'] ?? null,
                'is_active' => true,
            ]);

            // 2) Create user WITH password_hash (required / NOT NULL)
            // Your User model hashes automatically via setPasswordHashAttribute().
            $user = User::create([
                'role' => UserRole::Company,
                'email' => $data['user']['email'],
                'password_hash' => $data['password'], // <-- IMPORTANT
                'first_name' => $data['user']['first_name'],
                'middle_name' => $data['user']['middle_name'] ?? null,
                'last_name' => $data['user']['last_name'],
                'phone' => $data['user']['phone'] ?? null,
            ]);

            // 3) Link user -> company via company_users
            CompanyUser::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'job_title' => 'Owner',
            ]);

            // 4) Primary location (your request requires city/country so we create it)
            $location = CompanyLocation::create([
                'company_id' => $company->id,
                'address_line' => $data['location']['address_line'] ?? null,
                'postal_code' => $data['location']['postal_code'] ?? null,
                'city' => $data['location']['city'],
                'country' => $data['location']['country'],
                'lat' => $data['location']['lat'] ?? null,
                'lon' => $data['location']['lon'] ?? null,
                'is_primary' => true,
            ]);

            // 5) Issue JWT
            $token = JWTAuth::fromUser($user);

            return compact('company', 'user', 'location', 'token');
        });

        return response()->json([
            'data' => [
                'company' => [
                    'id' => $out['company']->id,
                    'name' => $out['company']->name,
                    'industry_tag_id' => $out['company']->industry_tag_id,
                    'email' => $out['company']->email,
                    'phone' => $out['company']->phone,
                    'size_category' => $out['company']->size_category,
                    'photo_url' => $out['company']->photo_url,
                    'is_active' => (bool) $out['company']->is_active,
                    'created_at' => optional($out['company']->created_at)?->toISOString(),
                    'updated_at' => optional($out['company']->updated_at)?->toISOString(),
                ],
                'user' => [
                    'id' => $out['user']->id,
                    'role' => (string) $out['user']->role,
                    'email' => $out['user']->email,
                    'first_name' => $out['user']->first_name,
                    'middle_name' => $out['user']->middle_name,
                    'last_name' => $out['user']->last_name,
                    'phone' => $out['user']->phone,
                    'created_at' => optional($out['user']->created_at)?->toISOString(),
                    'updated_at' => optional($out['user']->updated_at)?->toISOString(),
                ],
                'location' => [
                    'id' => $out['location']->id,
                    'company_id' => $out['location']->company_id,
                    'address_line' => $out['location']->address_line,
                    'postal_code' => $out['location']->postal_code,
                    'city' => $out['location']->city,
                    'country' => $out['location']->country,
                    'lat' => $out['location']->lat,
                    'lon' => $out['location']->lon,
                    'is_primary' => (bool) $out['location']->is_primary,
                    'created_at' => optional($out['location']->created_at)?->toISOString(),
                    'updated_at' => optional($out['location']->updated_at)?->toISOString(),
                ],
            ],
            'meta' => [
                'token' => $out['token'],
                'token_type' => 'Bearer',
            ],
        ], 201);
    }
}
