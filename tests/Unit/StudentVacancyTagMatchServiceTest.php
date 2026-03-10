<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;
use App\Services\StudentVacancyTagMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVacancyTagMatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentVacancyTagMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentVacancyTagMatchService;
    }

    public function test_cosine_score_same_vectors_returns_100(): void
    {
        $vec = [1 => 1.0, 2 => 2.0, 3 => 1.0];
        $score = $this->service->cosineScore($vec, $vec);
        $this->assertSame(100.0, $score);
    }

    public function test_cosine_score_no_overlap_returns_0(): void
    {
        $vecA = [1 => 1.0, 2 => 1.0];
        $vecB = [3 => 1.0, 4 => 1.0];
        $score = $this->service->cosineScore($vecA, $vecB);
        $this->assertSame(0.0, $score);
    }

    public function test_cosine_score_empty_vector_returns_0(): void
    {
        $this->assertSame(0.0, $this->service->cosineScore([], [1 => 1.0]));
        $this->assertSame(0.0, $this->service->cosineScore([1 => 1.0], []));
        $this->assertSame(0.0, $this->service->cosineScore([], []));
    }

    public function test_score_high_when_student_and_vacancy_share_tags(): void
    {
        $tag = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tag->id,
            'is_active' => true,
            'weight' => 80,
        ]);

        $company = Company::create([
            'name' => 'Test Co',
            'is_active' => true,
        ]);
        $vacancy = Vacancy::create([
            'company_id' => $company->id,
            'title' => 'Backend dev',
        ]);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $tag->id,
            'requirement_type' => 'skill',
            'importance' => 10,
        ]);

        $score = $this->service->score($student->id, $vacancy->id);
        $this->assertGreaterThanOrEqual(99.0, $score);
    }

    public function test_extra_student_tags_do_not_lower_score(): void
    {
        $tagMatch = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $tagExtra = Tag::create(['name' => 'Python', 'tag_type' => 'skill', 'is_active' => true]);
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tagMatch->id,
            'is_active' => true,
            'weight' => 10,
        ]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tagExtra->id,
            'is_active' => true,
            'weight' => 10,
        ]);

        $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'PHP only']);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $tagMatch->id,
            'requirement_type' => 'skill',
            'importance' => 10,
        ]);

        $score = $this->service->score($student->id, $vacancy->id);
        $this->assertGreaterThanOrEqual(99.0, $score, 'Student with one matching and one extra skill should still get full score (vacancy-centric).');
    }

    public function test_score_zero_when_no_tag_overlap(): void
    {
        $tagA = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $tagB = Tag::create(['name' => 'Python', 'tag_type' => 'skill', 'is_active' => true]);
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tagA->id,
            'is_active' => true,
            'weight' => 80,
        ]);

        $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend dev']);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $tagB->id,
            'requirement_type' => 'skill',
            'importance' => 10,
        ]);

        $score = $this->service->score($student->id, $vacancy->id);
        $this->assertSame(0.0, $score);
    }

    public function test_score_with_subscores_returns_overall_and_categories(): void
    {
        $tag = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tag->id,
            'is_active' => true,
            'weight' => 90,
        ]);

        $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend dev']);
        VacancyRequirement::create([
            'vacancy_id' => $vacancy->id,
            'tag_id' => $tag->id,
            'requirement_type' => 'skill',
            'importance' => 5,
        ]);

        $result = $this->service->scoreWithSubscores($student->id, $vacancy->id);
        $this->assertArrayHasKey('overall', $result);
        $this->assertArrayHasKey('subscores', $result);
        $this->assertSame(['skill', 'trait'], array_keys($result['subscores']));
        foreach ($result['subscores'] as $cat => $sub) {
            $this->assertArrayHasKey('score', $sub);
            $this->assertArrayHasKey('explanation', $sub);
        }
        $this->assertGreaterThanOrEqual(99.0, $result['overall']);
    }

    public function test_overall_score_uses_only_skill_and_trait_major_industry_do_not_affect_score(): void
    {
        $skillTag = Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        $majorTag = Tag::create(['name' => 'Computer Science', 'tag_type' => 'major', 'is_active' => true]);
        $industryTag = Tag::create(['name' => 'Software Dev', 'tag_type' => 'industry', 'is_active' => true]);

        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create(['student_user_id' => $student->id, 'tag_id' => $skillTag->id, 'is_active' => true, 'weight' => 80]);
        StudentTag::create(['student_user_id' => $student->id, 'tag_id' => $majorTag->id, 'is_active' => true, 'weight' => 100]);
        StudentTag::create(['student_user_id' => $student->id, 'tag_id' => $industryTag->id, 'is_active' => true, 'weight' => 90]);

        $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
        $vacancy = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend']);
        VacancyRequirement::create(['vacancy_id' => $vacancy->id, 'tag_id' => $skillTag->id, 'requirement_type' => 'skill', 'importance' => 10]);
        VacancyRequirement::create(['vacancy_id' => $vacancy->id, 'tag_id' => $majorTag->id, 'requirement_type' => 'major', 'importance' => 5]);
        VacancyRequirement::create(['vacancy_id' => $vacancy->id, 'tag_id' => $industryTag->id, 'requirement_type' => 'industry', 'importance' => 5]);

        $scoreWithAll = $this->service->score($student->id, $vacancy->id);

        $vacancy2 = Vacancy::create(['company_id' => $company->id, 'title' => 'Backend 2']);
        VacancyRequirement::create(['vacancy_id' => $vacancy2->id, 'tag_id' => $skillTag->id, 'requirement_type' => 'skill', 'importance' => 10]);

        $scoreSkillOnly = $this->service->score($student->id, $vacancy2->id);

        $this->assertGreaterThanOrEqual(99.0, $scoreSkillOnly);
        $this->assertSame($scoreWithAll, $scoreSkillOnly);
    }

    public function test_when_student_has_major_only_vacancies_with_that_major_are_returned(): void
    {
        $majorTag = Tag::create(['name' => 'Computer Science', 'tag_type' => 'major', 'is_active' => true]);
        $otherMajorTag = Tag::create(['name' => 'Software Engineering', 'tag_type' => 'major', 'is_active' => true]);

        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        StudentTag::create(['student_user_id' => $student->id, 'tag_id' => $majorTag->id, 'is_active' => true, 'weight' => 100]);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        $vacancyWithMajor = Vacancy::create(['company_id' => $company->id, 'title' => 'Match major']);
        VacancyRequirement::create(['vacancy_id' => $vacancyWithMajor->id, 'tag_id' => $majorTag->id, 'requirement_type' => 'major', 'importance' => 5]);
        $vacancyWithoutMajor = Vacancy::create(['company_id' => $company->id, 'title' => 'Other major']);
        VacancyRequirement::create(['vacancy_id' => $vacancyWithoutMajor->id, 'tag_id' => $otherMajorTag->id, 'requirement_type' => 'major', 'importance' => 5]);

        $paginator = $this->service->vacanciesWithScoresForStudent($student->id, 15, 1);
        $this->assertSame(1, $paginator->total());
        $this->assertSame('Match major', $paginator->items()[0]['vacancy']->title);
    }

    public function test_when_student_has_no_major_all_active_vacancies_returned(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);

        $company = Company::create(['name' => 'Active Co', 'is_active' => true]);
        Vacancy::create(['company_id' => $company->id, 'title' => 'V1']);
        Vacancy::create(['company_id' => $company->id, 'title' => 'V2']);

        $paginator = $this->service->vacanciesWithScoresForStudent($student->id, 15, 1);
        $this->assertSame(2, $paginator->total());
    }

    public function test_when_industry_tag_id_passed_only_vacancies_from_companies_with_that_industry(): void
    {
        $industryA = Tag::create(['name' => 'Industry A', 'tag_type' => 'industry', 'is_active' => true]);
        $industryB = Tag::create(['name' => 'Industry B', 'tag_type' => 'industry', 'is_active' => true]);

        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);

        $companyA = Company::create(['name' => 'Co A', 'is_active' => true, 'industry_tag_id' => $industryA->id]);
        $companyB = Company::create(['name' => 'Co B', 'is_active' => true, 'industry_tag_id' => $industryB->id]);
        Vacancy::create(['company_id' => $companyA->id, 'title' => 'At A']);
        Vacancy::create(['company_id' => $companyB->id, 'title' => 'At B']);

        $paginator = $this->service->vacanciesWithScoresForStudent($student->id, 15, 1, $industryA->id);
        $this->assertSame(1, $paginator->total());
        $this->assertSame('At A', $paginator->items()[0]['vacancy']->title);
    }

    public function test_vacancies_with_scores_only_includes_active_companies(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);

        $activeCompany = Company::create(['name' => 'Active Co', 'is_active' => true]);
        $inactiveCompany = Company::create(['name' => 'Inactive Co', 'is_active' => false]);
        Vacancy::create(['company_id' => $activeCompany->id, 'title' => 'Open role']);
        Vacancy::create(['company_id' => $inactiveCompany->id, 'title' => 'Hidden role']);

        $paginator = $this->service->vacanciesWithScoresForStudent($student->id, 15, 1);
        $this->assertSame(1, $paginator->total());
        $this->assertSame('Open role', $paginator->items()[0]['vacancy']->title);
    }
}
