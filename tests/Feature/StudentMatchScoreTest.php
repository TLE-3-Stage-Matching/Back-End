<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ApiKey;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\StudentProfile;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentMatchScoreTest extends TestCase
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

    public function test_student_vacancy_detail_includes_match_result(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Dev role']);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/v2/student/vacancies/{$vacancy->id}/detail");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'vacancy' => ['id', 'title', 'company'],
                    'match_result' => [
                        'match_score',
                        'subscores' => [
                            'must_have' => ['score', 'explanation'],
                            'nice_to_have' => ['score', 'explanation'],
                            'combined' => ['score', 'explanation'],
                            'penalty' => ['score', 'explanation'],
                        ],
                    ],
                    'score',
                    'breakdown',
                ],
                'links' => ['self'],
            ]);
    }

    public function test_student_can_get_vacancies_with_scores(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        Vacancy::create(['company_id' => $company->id, 'title' => 'Dev role']);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v2/student/vacancies-with-scores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'vacancy' => ['id', 'title', 'company'],
                        'match_score',
                        'subscores' => [
                            'must_have' => ['score'],
                            'nice_to_have' => ['score'],
                            'combined' => ['score'],
                            'penalty' => ['score'],
                        ],
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['self'],
            ]);
    }

    public function test_non_student_cannot_access_vacancies_with_scores(): void
    {
        $apiKey = $this->makeApiKey();
        $coordinator = User::factory()->coordinator()->create();
        $token = JWTAuth::fromUser($coordinator);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v2/student/vacancies-with-scores');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden. Student role required.']);
    }

    public function test_unauthenticated_user_cannot_access_vacancies_with_scores(): void
    {
        $apiKey = $this->makeApiKey();
        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->getJson('/api/v2/student/vacancies-with-scores');

        $response->assertStatus(401);
    }

    public function test_coordinator_can_get_student_vacancies_with_scores(): void
    {
        $apiKey = $this->makeApiKey();
        $coordinator = User::factory()->coordinator()->create();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($coordinator);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        Vacancy::create(['company_id' => $company->id, 'title' => 'Dev role']);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/v2/coordinator/students/{$student->id}/vacancies-with-scores");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['self'],
            ]);
    }

    public function test_coordinator_gets_404_when_user_is_not_student(): void
    {
        $apiKey = $this->makeApiKey();
        $coordinator = User::factory()->coordinator()->create();
        $companyUser = User::factory()->company()->create();
        $token = JWTAuth::fromUser($coordinator);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/v2/coordinator/students/{$companyUser->id}/vacancies-with-scores");

        $response->assertStatus(404)
            ->assertJson(['message' => 'User is not a student.']);
    }

    public function test_student_cannot_access_coordinator_student_vacancies_endpoint(): void
    {
        $apiKey = $this->makeApiKey();
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $otherStudent = User::factory()->student()->create(['email' => 'other@example.com']);
        StudentProfile::create(['user_id' => $otherStudent->id]);
        $token = JWTAuth::fromUser($student);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/v2/coordinator/students/{$otherStudent->id}/vacancies-with-scores");

        $response->assertStatus(403);
    }

    public function test_vacancy_create_with_six_major_tags_returns_422(): void
    {
        $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
        $user = User::factory()->company()->create(['email' => 'hr@test.com']);
        CompanyUser::create(['user_id' => $user->id, 'company_id' => $company->id, 'job_title' => 'HR']);
        $token = JWTAuth::fromUser($user);

        $majorIds = [];
        foreach (['Computer Science', 'Software Engineering', 'Information Technology', 'Information Systems', 'Computer Engineering', 'Data Science'] as $name) {
            $majorIds[] = Tag::create(['name' => $name, 'tag_type' => 'major', 'is_active' => true])->id;
        }

        $tags = array_map(fn ($id) => ['id' => $id, 'requirement_type' => 'major'], $majorIds);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/company/vacancies', [
            'title' => 'Backend role',
            'tags' => $tags,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_student_sync_tags_with_two_majors_returns_422(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $major1 = Tag::create(['name' => 'Computer Science', 'tag_type' => 'major', 'is_active' => true]);
        $major2 = Tag::create(['name' => 'Software Engineering', 'tag_type' => 'major', 'is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson('/api/v1/student/tags', [
            'tags' => [
                ['tag_id' => $major1->id, 'is_active' => true, 'weight' => 80],
                ['tag_id' => $major2->id, 'is_active' => true, 'weight' => 70],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_vacancies_with_scores_industry_tag_id_filters_by_company_industry(): void
    {
        $apiKey = $this->makeApiKey();
        $industryA = Tag::create(['name' => 'Industry A', 'tag_type' => 'industry', 'is_active' => true]);
        $industryB = Tag::create(['name' => 'Industry B', 'tag_type' => 'industry', 'is_active' => true]);

        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = JWTAuth::fromUser($student);

        $companyA = Company::create(['name' => 'Co A', 'is_active' => true, 'industry_tag_id' => $industryA->id]);
        $companyB = Company::create(['name' => 'Co B', 'is_active' => true, 'industry_tag_id' => $industryB->id]);
        Vacancy::create(['company_id' => $companyA->id, 'title' => 'At A']);
        Vacancy::create(['company_id' => $companyB->id, 'title' => 'At B']);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v2/student/vacancies-with-scores?industry_tag_id=' . $industryA->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.vacancy.title', 'At A');
        $response->assertJsonPath('data.0.subscores.must_have', fn ($s) => is_array($s) && array_key_exists('score', $s));
        $response->assertJsonPath('data.0.subscores.nice_to_have', fn ($s) => is_array($s) && array_key_exists('score', $s));
    }
}
