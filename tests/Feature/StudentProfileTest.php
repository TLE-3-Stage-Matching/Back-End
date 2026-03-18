<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\LanguageLevel;
use App\Models\StudentExperience;
use App\Models\StudentLanguage;
use App\Models\StudentPreference;
use App\Models\StudentProfile;
use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a student user
        $this->student = User::factory()->student()->create();

        // Create empty student profile
        StudentProfile::create(['user_id' => $this->student->id]);

        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->student);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /student/profile
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_get_own_profile(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'role',
                    'email',
                    'first_name',
                    'last_name',
                    'student_profile',
                    'student_experiences',
                    'student_tags',
                    'student_languages',
                    'student_preferences',
                ],
                'links' => ['self'],
            ])
            ->assertJsonPath('data.role', 'student');
    }

    public function test_non_student_cannot_access_student_profile(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $coordinatorToken = JWTAuth::fromUser($coordinator);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $coordinatorToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/profile');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden. Student role required.']);
    }

    public function test_unauthenticated_user_cannot_access_student_profile(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/profile');

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────
    // PUT/PATCH /student/profile
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_update_user_fields(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'first_name' => 'UpdatedFirstName',
            'last_name' => 'UpdatedLastName',
            'phone' => '+31612345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'UpdatedFirstName')
            ->assertJsonPath('data.last_name', 'UpdatedLastName')
            ->assertJsonPath('data.phone', '+31612345678');

        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'first_name' => 'UpdatedFirstName',
            'last_name' => 'UpdatedLastName',
        ]);
    }

    public function test_student_can_update_profile_fields(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'headline' => 'Junior Full-Stack Developer',
            'bio' => 'Passionate about Laravel and Vue.js',
            'city' => 'Amsterdam',
            'country' => 'Netherlands',
            'searching_status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.student_profile.headline', 'Junior Full-Stack Developer')
            ->assertJsonPath('data.student_profile.bio', 'Passionate about Laravel and Vue.js')
            ->assertJsonPath('data.student_profile.city', 'Amsterdam');

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $this->student->id,
            'headline' => 'Junior Full-Stack Developer',
            'city' => 'Amsterdam',
        ]);
    }

    public function test_student_can_update_email(): void
    {
        $newEmail = 'newemail@example.com';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'email' => $newEmail,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $newEmail);

        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'email' => $newEmail,
        ]);
    }

    public function test_student_cannot_use_duplicate_email(): void
    {
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_student_can_update_password(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'password' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200);

        // Verify can login with new password
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->student->email,
            'password' => 'NewSecurePassword123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_student_can_update_privacy_settings(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'exclude_demographics' => true,
            'exclude_location' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.student_profile.exclude_demographics', true)
            ->assertJsonPath('data.student_profile.exclude_location', true);
    }

    // ─────────────────────────────────────────────────────────────
    // Student Preferences
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_get_preferences(): void
    {
        StudentPreference::create([
            'student_user_id' => $this->student->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'desired_role_tag_id',
                    'hours_per_week_min',
                    'hours_per_week_max',
                    'max_distance_km',
                    'has_drivers_license',
                    'notes',
                    'desired_role_tag',
                    'favorite_companies',
                ],
                'links' => ['self'],
            ]);
    }

    public function test_student_can_update_preferences(): void
    {
        $tag = Tag::create(['name' => 'Backend Developer', 'tag_type' => 'role']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/preferences', [
            'desired_role_tag_id' => $tag->id,
            'hours_per_week_min' => 32,
            'hours_per_week_max' => 40,
            'max_distance_km' => 50,
            'has_drivers_license' => true,
            'notes' => 'Prefer remote work',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.desired_role_tag_id', $tag->id)
            ->assertJsonPath('data.hours_per_week_min', 32)
            ->assertJsonPath('data.hours_per_week_max', 40)
            ->assertJsonPath('data.has_drivers_license', true)
            ->assertJsonStructure(['data' => ['favorite_companies']]);

        $this->assertDatabaseHas('student_preferences', [
            'student_user_id' => $this->student->id,
            'hours_per_week_min' => 32,
        ]);
    }

    public function test_preferences_validation_max_must_be_gte_min(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/preferences', [
            'hours_per_week_min' => 40,
            'hours_per_week_max' => 20,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hours_per_week_max']);
    }

    // ─────────────────────────────────────────────────────────────
    // Student Experiences CRUD
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_list_experiences(): void
    {
        StudentExperience::create([
            'student_user_id' => $this->student->id,
            'title' => 'Intern',
            'company_name' => 'Acme Corp',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/experiences');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Intern');
    }

    public function test_student_can_create_experience(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/student/experiences', [
            'title' => 'Software Developer Intern',
            'company_name' => 'Tech Solutions BV',
            'start_date' => '2025-01-15',
            'end_date' => '2025-06-30',
            'description' => 'Developed backend APIs using Laravel',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Software Developer Intern')
            ->assertJsonPath('data.company_name', 'Tech Solutions BV');

        $this->assertDatabaseHas('student_experiences', [
            'student_user_id' => $this->student->id,
            'title' => 'Software Developer Intern',
        ]);
    }

    public function test_experience_end_date_must_be_after_start_date(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/student/experiences', [
            'title' => 'Intern',
            'company_name' => 'Acme Corp',
            'start_date' => '2025-06-30',
            'end_date' => '2025-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_student_can_update_own_experience(): void
    {
        $experience = StudentExperience::create([
            'student_user_id' => $this->student->id,
            'title' => 'Intern',
            'company_name' => 'Acme Corp',
            'start_date' => '2024-01-01',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/experiences/' . $experience->id, [
            'title' => 'Junior Developer',
            'description' => 'Promoted from intern',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Junior Developer')
            ->assertJsonPath('data.description', 'Promoted from intern');
    }

    public function test_student_cannot_update_other_students_experience(): void
    {
        $otherStudent = User::factory()->student()->create();
        $experience = StudentExperience::create([
            'student_user_id' => $otherStudent->id,
            'title' => 'Intern',
            'company_name' => 'Other Corp',
            'start_date' => '2024-01-01',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/experiences/' . $experience->id, [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(404);
    }

    public function test_student_can_delete_own_experience(): void
    {
        $experience = StudentExperience::create([
            'student_user_id' => $this->student->id,
            'title' => 'Intern',
            'company_name' => 'Acme Corp',
            'start_date' => '2024-01-01',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/v1/student/experiences/' . $experience->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Experience deleted successfully.']);

        $this->assertDatabaseMissing('student_experiences', [
            'id' => $experience->id,
        ]);
    }

    public function test_student_cannot_delete_other_students_experience(): void
    {
        $otherStudent = User::factory()->student()->create();
        $experience = StudentExperience::create([
            'student_user_id' => $otherStudent->id,
            'title' => 'Intern',
            'company_name' => 'Other Corp',
            'start_date' => '2024-01-01',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/v1/student/experiences/' . $experience->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('student_experiences', [
            'id' => $experience->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Student Languages Sync
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_list_languages(): void
    {
        $language = Language::create(['name' => 'English']);
        $level = LanguageLevel::create(['name' => 'Fluent']);

        StudentLanguage::create([
            'student_user_id' => $this->student->id,
            'language_id' => $language->id,
            'language_level_id' => $level->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/languages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.language.name', 'English');
    }

    public function test_student_can_sync_languages(): void
    {
        $english = Language::create(['name' => 'English']);
        $dutch = Language::create(['name' => 'Dutch']);
        $fluent = LanguageLevel::create(['name' => 'Fluent']);
        $basic = LanguageLevel::create(['name' => 'Basic']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/languages', [
            'languages' => [
                ['language_id' => $english->id, 'language_level_id' => $fluent->id, 'is_active' => true],
                ['language_id' => $dutch->id, 'language_level_id' => $basic->id, 'is_active' => true],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('student_languages', 2);
    }

    public function test_sync_languages_replaces_existing(): void
    {
        $english = Language::create(['name' => 'English']);
        $dutch = Language::create(['name' => 'Dutch']);
        $german = Language::create(['name' => 'German']);
        $fluent = LanguageLevel::create(['name' => 'Fluent']);

        // Create initial languages
        StudentLanguage::create([
            'student_user_id' => $this->student->id,
            'language_id' => $english->id,
            'language_level_id' => $fluent->id,
        ]);
        StudentLanguage::create([
            'student_user_id' => $this->student->id,
            'language_id' => $dutch->id,
            'language_level_id' => $fluent->id,
        ]);

        // Sync with only German
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/languages', [
            'languages' => [
                ['language_id' => $german->id, 'language_level_id' => $fluent->id],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.language.name', 'German');

        $this->assertDatabaseCount('student_languages', 1);
    }

    // ─────────────────────────────────────────────────────────────
    // Student Tags Sync
    // ─────────────────────────────────────────────────────────────

    public function test_student_can_list_tags(): void
    {
        $tag = Tag::create(['name' => 'PHP', 'tag_type' => 'skill']);

        StudentTag::create([
            'student_user_id' => $this->student->id,
            'tag_id' => $tag->id,
            'is_active' => true,
            'weight' => 5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/tags');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tag.name', 'PHP')
            ->assertJsonPath('data.0.weight', 5);
    }

    public function test_student_can_sync_tags(): void
    {
        $php = Tag::create(['name' => 'PHP', 'tag_type' => 'skill']);
        $laravel = Tag::create(['name' => 'Laravel', 'tag_type' => 'skill']);
        $vue = Tag::create(['name' => 'Vue.js', 'tag_type' => 'skill']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/tags', [
            'tags' => [
                ['tag_id' => $php->id, 'is_active' => true, 'weight' => 5],
                ['tag_id' => $laravel->id, 'is_active' => true, 'weight' => 4],
                ['tag_id' => $vue->id, 'is_active' => true, 'weight' => 3],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $this->assertDatabaseCount('student_tags', 3);
        $this->assertDatabaseHas('student_tags', [
            'student_user_id' => $this->student->id,
            'tag_id' => $php->id,
            'weight' => 5,
        ]);
    }

    public function test_sync_tags_replaces_existing(): void
    {
        $php = Tag::create(['name' => 'PHP', 'tag_type' => 'skill']);
        $python = Tag::create(['name' => 'Python', 'tag_type' => 'skill']);

        // Create initial tag
        StudentTag::create([
            'student_user_id' => $this->student->id,
            'tag_id' => $php->id,
            'is_active' => true,
        ]);

        // Sync with only Python
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/tags', [
            'tags' => [
                ['tag_id' => $python->id, 'is_active' => true, 'weight' => 4],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tag.name', 'Python');

        $this->assertDatabaseMissing('student_tags', [
            'student_user_id' => $this->student->id,
            'tag_id' => $php->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Full Profile Integration Test
    // ─────────────────────────────────────────────────────────────

    public function test_full_profile_update_workflow(): void
    {
        // Create supporting data
        $tag = Tag::create(['name' => 'PHP', 'tag_type' => 'skill']);
        $language = Language::create(['name' => 'English']);
        $level = LanguageLevel::create(['name' => 'Native']);

        // Step 1: Update basic profile
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/profile', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'headline' => 'Full-Stack Developer',
            'city' => 'Amsterdam',
        ])->assertStatus(200);

        // Step 2: Update preferences
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/student/preferences', [
            'hours_per_week_min' => 32,
            'hours_per_week_max' => 40,
            'has_drivers_license' => true,
        ])->assertStatus(200);

        // Step 3: Add experience
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/student/experiences', [
            'title' => 'Intern',
            'company_name' => 'Acme Corp',
            'start_date' => '2025-01-01',
        ])->assertStatus(201);

        // Step 4: Sync languages
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/languages', [
            'languages' => [
                ['language_id' => $language->id, 'language_level_id' => $level->id],
            ],
        ])->assertStatus(200);

        // Step 5: Sync tags
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/tags', [
            'tags' => [
                ['tag_id' => $tag->id, 'weight' => 5],
            ],
        ])->assertStatus(200);

        // Step 6: Verify full profile
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/student/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.student_profile.headline', 'Full-Stack Developer')
            ->assertJsonPath('data.student_preferences.hours_per_week_min', 32)
            ->assertJsonCount(1, 'data.student_experiences')
            ->assertJsonCount(1, 'data.student_languages')
            ->assertJsonCount(1, 'data.student_tags');
    }
}

