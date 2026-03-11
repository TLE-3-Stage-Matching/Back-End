<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\StoreUserRequest;
use App\Http\Requests\Coordinator\UpdateUserRequest;
use App\Models\CompanyUser;
use App\Models\StudentCoordinatorAssignment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StageCoordinatorUserController extends Controller
{
    /**
     * List users that coordinators can manage (students and company users).
     * Use role=student for students only, role=company for company users only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['companyUser.company', 'studentProfile'])
            ->whereIn('role', [UserRole::Student, UserRole::Company]);

        if ($request->filled('role') && in_array($request->role, [UserRole::Student->value, UserRole::Company->value], true)) {
            $query->where('role', $request->role);
        }
        if ($request->boolean('assigned_to_me') && $request->input('role') === UserRole::Student->value) {
            $query->whereHas('coordinatorAssignments', function ($q) {
                $q->where('coordinator_user_id', auth('api')->id())
                    ->whereNull('unassigned_at');
            });
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
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
            StudentProfile::create([
                'user_id' => $user->id,
            ]);

            StudentCoordinatorAssignment::create([
                'student_user_id' => $user->id,
                'coordinator_user_id' => auth('api')->id(),
                'assigned_by_user_id' => auth('api')->id(),
                'assigned_at' => now(),
                'unassigned_at' => null,
            ]);
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

        if ($user->role === UserRole::Student) {
            $user->load([
                'studentProfile',
                'studentExperiences',
                'studentTags.tag',
                'studentLanguages.language',
                'studentLanguages.languageLevel',
                'studentPreferences.desiredRoleTag',
                'studentFavoriteCompanies.company',
                'studentSavedVacancies.vacancy',
            ]);
        } else {
            $user->load(['companyUser.company']);
        }

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
            $data['student_profile'] = [
                'user_id' => $user->studentProfile->user_id,
                'headline' => $user->studentProfile->headline,
                'bio' => $user->studentProfile->bio,
                'address_line' => $user->studentProfile->address_line,
                'postal_code' => $user->studentProfile->postal_code,
                'city' => $user->studentProfile->city,
                'country' => $user->studentProfile->country,
                'searching_status' => $user->studentProfile->searching_status,
                'exclude_demographics' => $user->studentProfile->exclude_demographics,
                'exclude_location' => $user->studentProfile->exclude_location,
            ];
        }

        if ($user->relationLoaded('studentExperiences')) {
            $data['student_experiences'] = $user->studentExperiences->map(fn ($exp) => [
                'id' => $exp->id,
                'title' => $exp->title,
                'company_name' => $exp->company_name,
                'start_date' => $exp->start_date?->toDateString(),
                'end_date' => $exp->end_date?->toDateString(),
                'description' => $exp->description,
            ])->toArray();
        }

        if ($user->relationLoaded('studentTags')) {
            $data['student_tags'] = $user->studentTags->map(fn ($st) => [
                'tag_id' => $st->tag_id,
                'is_active' => $st->is_active,
                'weight' => $st->weight,
                'tag' => $st->relationLoaded('tag') && $st->tag ? [
                    'id' => $st->tag->id,
                    'name' => $st->tag->name,
                    'tag_type' => $st->tag->tag_type,
                ] : null,
            ])->toArray();
        }

        if ($user->relationLoaded('studentLanguages')) {
            $data['student_languages'] = $user->studentLanguages->map(fn ($sl) => [
                'language_id' => $sl->language_id,
                'language_level_id' => $sl->language_level_id,
                'is_active' => $sl->is_active,
                'language' => $sl->relationLoaded('language') && $sl->language ? [
                    'id' => $sl->language->id,
                    'name' => $sl->language->name,
                ] : null,
                'language_level' => $sl->relationLoaded('languageLevel') && $sl->languageLevel ? [
                    'id' => $sl->languageLevel->id,
                    'name' => $sl->languageLevel->name,
                ] : null,
            ])->toArray();
        }

        if ($user->relationLoaded('studentPreferences') && $user->studentPreferences) {
            $data['student_preferences'] = [
                'desired_role_tag_id' => $user->studentPreferences->desired_role_tag_id,
                'hours_per_week_min' => $user->studentPreferences->hours_per_week_min,
                'hours_per_week_max' => $user->studentPreferences->hours_per_week_max,
                'max_distance_km' => $user->studentPreferences->max_distance_km,
                'has_drivers_license' => $user->studentPreferences->has_drivers_license,
                'notes' => $user->studentPreferences->notes,
                'desired_role_tag' => $user->studentPreferences->relationLoaded('desiredRoleTag') && $user->studentPreferences->desiredRoleTag ? [
                    'id' => $user->studentPreferences->desiredRoleTag->id,
                    'name' => $user->studentPreferences->desiredRoleTag->name,
                    'tag_type' => $user->studentPreferences->desiredRoleTag->tag_type,
                ] : null,
            ];
        }

        if ($user->relationLoaded('studentFavoriteCompanies')) {
            $data['student_favorite_companies'] = $user->studentFavoriteCompanies->map(fn ($fc) => [
                'company_id' => $fc->company_id,
                'company' => $fc->relationLoaded('company') && $fc->company ? [
                    'id' => $fc->company->id,
                    'name' => $fc->company->name,
                ] : null,
            ])->toArray();
        }

        if ($user->relationLoaded('studentSavedVacancies')) {
            $data['student_saved_vacancies'] = $user->studentSavedVacancies->map(fn ($sv) => [
                'vacancy_id' => $sv->vacancy_id,
                'removed_at' => $sv->removed_at?->toIso8601String(),
                'vacancy' => $sv->relationLoaded('vacancy') && $sv->vacancy ? [
                    'id' => $sv->vacancy->id,
                    'title' => $sv->vacancy->title,
                    'company_id' => $sv->vacancy->company_id,
                ] : null,
            ])->toArray();
        }

        return $data;
    }
}
