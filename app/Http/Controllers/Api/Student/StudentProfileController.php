<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Requests\Student\UpdateStudentPreferencesRequest;
use App\Http\Requests\Student\StoreStudentExperienceRequest;
use App\Http\Requests\Student\UpdateStudentExperienceRequest;
use App\Http\Requests\Student\SyncStudentLanguagesRequest;
use App\Http\Requests\Student\SyncStudentTagsRequest;
use App\Models\Company;
use App\Models\StudentExperience;
use App\Models\StudentLanguage;
use App\Models\StudentPreference;
use App\Models\StudentProfile;
use App\Models\StudentTag;
use Illuminate\Http\JsonResponse;

class StudentProfileController extends Controller
{
    /**
     * Get the authenticated student's full profile.
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $user->load([
            'studentProfile',
            'studentExperiences',
            'studentTags.tag',
            'studentLanguages.language',
            'studentLanguages.languageLevel',
            'studentPreferences.desiredRoleTag',
        ]);

        return response()->json([
            'data' => $this->formatStudentProfile($user),
            'links' => ['self' => url('/api/v1/student/profile')],
        ]);
    }

    /**
     * Update the authenticated student's basic profile (user + student_profile).
     */
    public function update(UpdateStudentProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Update user fields
        $userFields = ['first_name', 'middle_name', 'last_name', 'phone', 'email'];
        foreach ($userFields as $field) {
            if (array_key_exists($field, $validated)) {
                $user->$field = $validated[$field];
            }
        }
        if (!empty($validated['password'])) {
            $user->password_hash = $validated['password'];
        }
        $user->save();

        // Update or create student profile
        $profileFields = ['headline', 'bio', 'address_line', 'postal_code', 'city', 'country', 'searching_status', 'exclude_demographics', 'exclude_location'];
        $profileData = array_intersect_key($validated, array_flip($profileFields));

        if (!empty($profileData)) {
            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        $user->load([
            'studentProfile',
            'studentExperiences',
            'studentTags.tag',
            'studentLanguages.language',
            'studentLanguages.languageLevel',
            'studentPreferences.desiredRoleTag',
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $this->formatStudentProfile($user),
            'links' => ['self' => url('/api/v1/student/profile')],
        ]);
    }

    /**
     * Update the authenticated student's preferences.
     */
    public function updatePreferences(UpdateStudentPreferencesRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        StudentPreference::updateOrCreate(
            ['student_user_id' => $user->id],
            $validated
        );

        $user->load([
            'studentPreferences.desiredRoleTag',
            'studentFavoriteCompanies.company.industryTag',
        ]);

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'data' => $this->formatPreferences($user->studentPreferences),
            'links' => ['self' => url('/api/v1/student/preferences')],
        ]);
    }

    /**
     * Get the authenticated student's preferences.
     */
    public function showPreferences(): JsonResponse
    {
        $user = auth()->user();
        $user->load([
            'studentPreferences.desiredRoleTag',
            'studentFavoriteCompanies.company.industryTag',
        ]);

        return response()->json([
            'data' => $this->formatPreferences($user->studentPreferences),
            'links' => ['self' => url('/api/v1/student/preferences')],
        ]);
    }

    /**
     * List the authenticated student's experiences.
     */
    public function listExperiences(): JsonResponse
    {
        $user = auth()->user();
        $experiences = $user->studentExperiences()->orderBy('start_date', 'desc')->get();

        return response()->json([
            'data' => $experiences->map(fn($exp) => $this->formatExperience($exp))->toArray(),
            'links' => ['self' => url('/api/v1/student/experiences')],
        ]);
    }

    /**
     * Create a new experience for the authenticated student.
     */
    public function storeExperience(StoreStudentExperienceRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $validated['student_user_id'] = $user->id;

        $experience = StudentExperience::create($validated);

        return response()->json([
            'message' => 'Experience created successfully.',
            'data' => $this->formatExperience($experience),
            'links' => ['self' => url('/api/v1/student/experiences/' . $experience->id)],
        ], 201);
    }

    /**
     * Update an existing experience for the authenticated student.
     */
    public function updateExperience(UpdateStudentExperienceRequest $request, StudentExperience $experience): JsonResponse
    {
        $user = auth()->user();

        if ($experience->student_user_id !== $user->id) {
            return response()->json(['message' => 'Experience not found.'], 404);
        }

        $experience->update($request->validated());

        return response()->json([
            'message' => 'Experience updated successfully.',
            'data' => $this->formatExperience($experience),
            'links' => ['self' => url('/api/v1/student/experiences/' . $experience->id)],
        ]);
    }

    /**
     * Delete an experience for the authenticated student.
     */
    public function destroyExperience(StudentExperience $experience): JsonResponse
    {
        $user = auth()->user();

        if ($experience->student_user_id !== $user->id) {
            return response()->json(['message' => 'Experience not found.'], 404);
        }

        $experience->delete();

        return response()->json(['message' => 'Experience deleted successfully.']);
    }

    /**
     * Sync the authenticated student's languages (replace all).
     */
    public function syncLanguages(SyncStudentLanguagesRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Delete existing languages
        StudentLanguage::where('student_user_id', $user->id)->delete();

        // Create new languages
        foreach ($validated['languages'] as $langData) {
            StudentLanguage::create([
                'student_user_id' => $user->id,
                'language_id' => $langData['language_id'],
                'language_level_id' => $langData['language_level_id'],
                'is_active' => $langData['is_active'] ?? true,
            ]);
        }

        $user->load('studentLanguages.language', 'studentLanguages.languageLevel');

        return response()->json([
            'message' => 'Languages updated successfully.',
            'data' => $user->studentLanguages->map(fn($sl) => $this->formatLanguage($sl))->toArray(),
            'links' => ['self' => url('/api/v1/student/languages')],
        ]);
    }

    /**
     * Get the authenticated student's languages.
     */
    public function listLanguages(): JsonResponse
    {
        $user = auth()->user();
        $user->load('studentLanguages.language', 'studentLanguages.languageLevel');

        return response()->json([
            'data' => $user->studentLanguages->map(fn($sl) => $this->formatLanguage($sl))->toArray(),
            'links' => ['self' => url('/api/v1/student/languages')],
        ]);
    }

    /**
     * Sync the authenticated student's tags/skills (replace all).
     */
    public function syncTags(SyncStudentTagsRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $tagType = request()->query('tag_type');

        // ❗ alleen tags van dit type verwijderen
        StudentTag::where('student_user_id', $user->id)
            ->whereHas('tag', function ($q) use ($tagType) {
                if ($tagType) {
                    $q->where('tag_type', $tagType);
                }
            })
            ->delete();

        // ❗ nieuwe tags toevoegen (mag leeg zijn)
        foreach ($validated['tags'] ?? [] as $tagData) {
            StudentTag::create([
                'student_user_id' => $user->id,
                'tag_id' => $tagData['tag_id'],
                'is_active' => $tagData['is_active'] ?? true,
                'weight' => $tagData['weight'] ?? 3,
            ]);
        }

        $user->load('studentTags.tag');

        $tags = $user->studentTags->filter(function ($st) use ($tagType) {
            return !$tagType || $st->tag?->tag_type === $tagType;
        });

        return response()->json([
            'message' => 'Tags updated successfully.',
            'data' => $tags->map(fn($st) => $this->formatTag($st))->values(),
            'links' => ['self' => url('/api/v1/student/tags')],
        ]);
    }

    /**
     * Get the authenticated student's tags/skills.
     */
    public function listTags(): JsonResponse
    {
        $user = auth()->user();
        $tagType = request()->query('tag_type');

        $user->load(['studentTags.tag']);

        $tags = $user->studentTags->filter(function ($st) use ($tagType) {
            return !$tagType || $st->tag?->tag_type === $tagType;
        });

        return response()->json([
            'data' => $tags->map(fn($st) => $this->formatTag($st))->values(),
            'links' => ['self' => url('/api/v1/student/tags')],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Formatting helpers
    // ─────────────────────────────────────────────────────────────

    private function formatStudentProfile($user): array
    {
        return [
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
            'student_profile' => $user->studentProfile ? [
                'headline' => $user->studentProfile->headline,
                'bio' => $user->studentProfile->bio,
                'address_line' => $user->studentProfile->address_line,
                'postal_code' => $user->studentProfile->postal_code,
                'city' => $user->studentProfile->city,
                'country' => $user->studentProfile->country,
                'searching_status' => $user->studentProfile->searching_status,
                'exclude_demographics' => $user->studentProfile->exclude_demographics,
                'exclude_location' => $user->studentProfile->exclude_location,
            ] : null,
            'student_experiences' => $user->studentExperiences->map(fn($exp) => $this->formatExperience($exp))->toArray(),
            'student_tags' => $user->studentTags->map(fn($st) => $this->formatTag($st))->toArray(),
            'student_languages' => $user->studentLanguages->map(fn($sl) => $this->formatLanguage($sl))->toArray(),
            'student_preferences' => $this->formatPreferences($user->studentPreferences),
        ];
    }

    private function formatExperience(StudentExperience $exp): array
    {
        return [
            'id' => $exp->id,
            'title' => $exp->title,
            'company_name' => $exp->company_name,
            'start_date' => $exp->start_date?->toDateString(),
            'end_date' => $exp->end_date?->toDateString(),
            'description' => $exp->description,
        ];
    }

    private function formatPreferences(?StudentPreference $prefs): ?array
    {
        if (!$prefs) {
            return null;
        }

        $favoriteCompanies = null;
        $user = auth()->user();
        if ($user && $user->relationLoaded('studentFavoriteCompanies')) {
            $favoriteCompanies = $user->studentFavoriteCompanies
                ->map(fn ($fc) => $fc->relationLoaded('company') && $fc->company
                    ? $this->formatCompany($fc->company)
                    : null)
                ->filter()
                ->values()
                ->all();
        }

        return [
            'desired_role_tag_id' => $prefs->desired_role_tag_id,
            'hours_per_week_min' => $prefs->hours_per_week_min,
            'hours_per_week_max' => $prefs->hours_per_week_max,
            'max_distance_km' => $prefs->max_distance_km,
            'has_drivers_license' => $prefs->has_drivers_license,
            'compensation_numerical' => $prefs->compensation_numerical,
            'notes' => $prefs->notes,
            'desired_role_tag' => $prefs->relationLoaded('desiredRoleTag') && $prefs->desiredRoleTag ? [
                'id' => $prefs->desiredRoleTag->id,
                'name' => $prefs->desiredRoleTag->name,
                'tag_type' => $prefs->desiredRoleTag->tag_type,
            ] : null,
            'favorite_companies' => $favoriteCompanies,
        ];
    }

    private function formatCompany(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'industry_tag_id' => $company->industry_tag_id,
            'industry_tag' => $company->relationLoaded('industryTag') && $company->industryTag ? [
                'id' => $company->industryTag->id,
                'name' => $company->industryTag->name,
                'tag_type' => $company->industryTag->tag_type,
            ] : null,
            'email' => $company->email,
            'phone' => $company->phone,
            'size_category' => $company->size_category,
            'photo_url' => $company->photo_url,
            'banner_url' => $company->banner_url,
            'description' => $company->description,
            'is_active' => $company->is_active,
            'created_at' => $company->created_at?->toIso8601String(),
            'updated_at' => $company->updated_at?->toIso8601String(),
        ];
    }

    private function formatLanguage(StudentLanguage $sl): array
    {
        return [
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
        ];
    }

    private function formatTag(StudentTag $st): array
    {
        return [
            'tag_id' => $st->tag_id,
            'is_active' => $st->is_active,
            'weight' => $st->weight,
            'tag' => $st->relationLoaded('tag') && $st->tag ? [
                'id' => $st->tag->id,
                'name' => $st->tag->name,
                'tag_type' => $st->tag->tag_type,
            ] : null,
        ];
    }
}

