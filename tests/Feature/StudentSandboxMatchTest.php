<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentSandboxMatchTest extends TestCase
{
    use RefreshDatabase;

    private function makeApiKey(): string
    {
        $plain = 'test-api-key-' . bin2hex(random_bytes(16));
        ApiKey::query()->create([
            'name' => 'Test Key',
            'key_hash' => hash('sha256', $plain),
            'plain_key_preview' => substr($plain, 0, 16),
            'is_active' => true,
        ]);

        return $plain;
    }

    public function test_student_can_get_sandbox_top_matches_and_nothing_is_persisted(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $skill = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $trait = Tag::create(['name' => 'Teamwork', 'tag_type' => 'trait', 'is_active' => true]);

        // Real profile tag (should not be modified by sandbox)
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $skill->id,
            'is_active' => true,
            'weight' => 1,
        ]);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend dev']);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $skill->id,
            'requirement_type' => 'must_have',
            'importance' => 5,
        ]);

        $before = StudentTag::query()->where('student_user_id', $student->id)->get()->toArray();

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v2/student/sandbox/top-matches', [
            'tags' => [
                ['tag_id' => $skill->id, 'weight' => 5],
                ['tag_id' => $trait->id, 'weight' => 3],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('sandbox', true)
            ->assertJsonStructure([
                'sandbox',
                'data' => [
                    '*' => ['vacancy_id', 'title', 'company', 'score'],
                ],
            ]);

        $after = StudentTag::query()->where('student_user_id', $student->id)->get()->toArray();
        $this->assertSame($before, $after);
    }

    public function test_sandbox_rejects_non_skill_or_trait_tags(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $major = Tag::create(['name' => 'Computer Science', 'tag_type' => 'major', 'is_active' => true]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v2/student/sandbox/top-matches', [
            'tags' => [
                ['tag_id' => $major->id, 'weight' => 3],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_sandbox_rejects_over_limit_skill_tags(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $skills = [];
        for ($i = 0; $i < 7; $i++) {
            $skills[] = Tag::create(['name' => 'Skill ' . $i, 'tag_type' => 'skill', 'is_active' => true]);
        }

        $payload = ['tags' => array_map(
            fn (Tag $t) => ['tag_id' => $t->id, 'weight' => 3],
            $skills
        )];

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v2/student/sandbox/top-matches', $payload);

        $response->assertStatus(422);
    }

    public function test_sandbox_empty_tags_behaves_like_real_profile_for_single_vacancy_score(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $skill = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $skill->id,
            'is_active' => true,
            'weight' => 5,
        ]);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend dev']);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $skill->id,
            'requirement_type' => 'must_have',
            'importance' => 5,
        ]);

        $real = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/v2/student/vacancies/{$vacancy->id}");

        $real->assertStatus(200);
        $realScore = $real->json('data.score');

        $sandbox = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson("/api/v2/student/sandbox/vacancies/{$vacancy->id}", [
            'tags' => [],
        ]);

        $sandbox->assertStatus(200)
            ->assertJsonPath('sandbox', true);

        $sandboxScore = $sandbox->json('data.score');
        $this->assertSame($realScore, $sandboxScore);
    }
}

