<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\StoreUserRequest;
use App\Http\Requests\Coordinator\UpdateUserRequest;
use App\Models\CompanyUser;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StageCoordinatorUserController extends Controller
{
    /**
     * List users that coordinators can manage (students and company users).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->whereIn('role', [UserRole::Student, UserRole::Company]);

        if ($request->has('role') && in_array($request->role, [UserRole::Student->value, UserRole::Company->value], true)) {
            $query->where('role', $request->role);
        }

        // When active_companies_only=1, only return company users whose company is active (and all students)
        if ($request->boolean('active_companies_only')) {
            $query->where(function ($q) {
                $q->where('role', UserRole::Student)
                    ->orWhereHas('companyUser.company', fn ($cq) => $cq->where('is_active', true));
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(
            $request->integer('per_page', 15)
        );

        $items = $users->through(function (User $user) {
            return $this->formatUser($user);
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Create a student or company user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        $user = new User([
            'role' => $role,
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
        ]);
        $user->password_hash = $validated['password'];
        $user->save();

        if ($role === UserRole::Company) {
            CompanyUser::create([
                'user_id' => $user->id,
                'company_id' => $validated['company_id'],
                'job_title' => $validated['job_title'] ?? null,
            ]);
        }

        if ($role === UserRole::Student) {
            StudentProfile::create(['user_id' => $user->id]);
        }

        $user->load(['companyUser.company', 'studentProfile']);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->formatUser($user),
        ], 201);
    }

    /**
     * Show a single user (student or company).
     */
    public function show(User $user): JsonResponse
    {
        if (! in_array($user->role, [UserRole::Student, UserRole::Company], true)) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->load(['companyUser.company', 'studentProfile']);

        return response()->json(['data' => $this->formatUser($user)]);
    }

    /**
     * Update a student or company user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if (! in_array($user->role, [UserRole::Student, UserRole::Company], true)) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validated();

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (array_key_exists('password', $validated) && $validated['password'] !== null && $validated['password'] !== '') {
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

        if ($user->role === UserRole::Company && isset($validated['company_id'])) {
            $user->companyUser?->update([
                'company_id' => $validated['company_id'],
                'job_title' => $validated['job_title'] ?? $user->companyUser->job_title,
            ]);
        } elseif ($user->role === UserRole::Company && array_key_exists('job_title', $validated)) {
            $user->companyUser?->update(['job_title' => $validated['job_title']]);
        }

        $user->load(['companyUser.company', 'studentProfile']);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * Delete a student or company user.
     */
    public function destroy(User $user): JsonResponse
    {
        if (! in_array($user->role, [UserRole::Student, UserRole::Company], true)) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }

    private function formatUser(User $user): array
    {
        $data = [
            'id' => $user->id,
            'role' => $user->role->value,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        if ($user->relationLoaded('companyUser') && $user->companyUser) {
            $data['company_user'] = [
                'company_id' => $user->companyUser->company_id,
                'job_title' => $user->companyUser->job_title,
            ];
            if ($user->companyUser->relationLoaded('company')) {
                $data['company'] = [
                    'id' => $user->companyUser->company->id,
                    'name' => $user->companyUser->company->name,
                ];
            }
        }

        if ($user->relationLoaded('studentProfile') && $user->studentProfile) {
            $data['student_profile'] = ['user_id' => $user->studentProfile->user_id];
        }

        return $data;
    }
}
