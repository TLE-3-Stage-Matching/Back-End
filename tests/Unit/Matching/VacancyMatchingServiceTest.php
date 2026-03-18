<?php

declare(strict_types=1);

namespace Tests\Unit\Matching;

use App\Matching\DTOs\StudentTagDTO;
use App\Matching\DTOs\VacancyTagDTO;
use App\Matching\VacancyMatchingService;
use PHPUnit\Framework\TestCase;

class VacancyMatchingServiceTest extends TestCase
{
    private VacancyMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VacancyMatchingService();
    }

    /**
     * Test 1: Student with all must-have tags at weight 3 scores higher than missing one.
     */
    public function test_student_with_all_must_haves_scores_higher_than_missing_one(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 3),
        ];

        $studentWithBoth = [
            new StudentTagDTO(tagId: 1, weight: 3),
            new StudentTagDTO(tagId: 2, weight: 3),
        ];

        $studentMissingOne = [
            new StudentTagDTO(tagId: 1, weight: 3),
        ];

        $scoreWithBoth = $this->service->score($studentWithBoth, $vacancyTags)->score;
        $scoreMissingOne = $this->service->score($studentMissingOne, $vacancyTags)->score;

        $this->assertGreaterThan($scoreMissingOne, $scoreWithBoth);
    }

    /**
     * Test 2: Student with more complete skill set scores higher than with partial skills.
     */
    public function test_complete_skill_set_scores_higher(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 3),
        ];

        $studentComplete = [
            new StudentTagDTO(tagId: 1, weight: 3),
            new StudentTagDTO(tagId: 2, weight: 3),
        ];

        $studentPartial = [
            new StudentTagDTO(tagId: 1, weight: 3),
        ];

        $scoreComplete = $this->service->score($studentComplete, $vacancyTags)->score;
        $scorePartial = $this->service->score($studentPartial, $vacancyTags)->score;

        $this->assertGreaterThan($scorePartial, $scoreComplete);
    }

    /**
     * Test 3: Student missing all must-have tags scores 0 (due to penalty).
     */
    public function test_missing_all_must_haves_scores_zero(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 3, requirementType: 'nice_to_have', importance: 2),
            new VacancyTagDTO(tagId: 4, requirementType: 'nice_to_have', importance: 2),
        ];

        $studentHasOnlyNiceToHave = [
            new StudentTagDTO(tagId: 3, weight: 5),
            new StudentTagDTO(tagId: 4, weight: 5),
        ];

        $result = $this->service->score($studentHasOnlyNiceToHave, $vacancyTags);

        $this->assertEquals(0, $result->score);
    }

    /**
     * Test 4: Vacancy with no must-have tags awards S_MH = 1.0.
     */
    public function test_vacancy_with_no_must_haves_awards_s_mh_1(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'nice_to_have', importance: 2),
            new VacancyTagDTO(tagId: 2, requirementType: 'nice_to_have', importance: 2),
        ];

        $student = [
            new StudentTagDTO(tagId: 1, weight: 3),
            new StudentTagDTO(tagId: 2, weight: 3),
        ];

        $result = $this->service->score($student, $vacancyTags);
        $sMh = $result->dimensionDetail['s_mh'];

        $this->assertEquals(1.0, $sMh);
    }

    /**
     * Test 5: Vacancy with no nice-to-have tags awards S_NTH = 1.0.
     */
    public function test_vacancy_with_no_nice_to_haves_awards_s_nth_1(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 2),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 2),
        ];

        $student = [
            new StudentTagDTO(tagId: 1, weight: 3),
            new StudentTagDTO(tagId: 2, weight: 3),
        ];

        $result = $this->service->score($student, $vacancyTags);
        $sNth = $result->dimensionDetail['s_nth'];

        $this->assertEquals(1.0, $sNth);
    }

    /**
     * Test 6: Final score is always between 0 and 100 regardless of input.
     */
    public function test_final_score_clamped_0_to_100(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 1),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 1),
            new VacancyTagDTO(tagId: 3, requirementType: 'nice_to_have', importance: 1),
        ];

        $student = [
            new StudentTagDTO(tagId: 1, weight: 5),
            new StudentTagDTO(tagId: 2, weight: 5),
            new StudentTagDTO(tagId: 3, weight: 5),
        ];

        $result = $this->service->score($student, $vacancyTags);

        $this->assertGreaterThanOrEqual(0, $result->score);
        $this->assertLessThanOrEqual(100, $result->score);
    }

    /**
     * Test that ranking returns results sorted by score descending.
     */
    public function test_rank_for_student_returns_sorted_results(): void
    {
        $student = [
            new StudentTagDTO(tagId: 1, weight: 5),
            new StudentTagDTO(tagId: 2, weight: 3),
        ];

        $vacancy1Tags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 5),
        ];

        $vacancy2Tags = [
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 3),
        ];

        $vacanciesWithTags = [
            1 => $vacancy1Tags,
            2 => $vacancy2Tags,
        ];

        $results = $this->service->rankForStudent($student, $vacanciesWithTags);

        $this->assertCount(2, $results);
        $this->assertGreaterThanOrEqual($results[1]->score, $results[0]->score);
    }

    /**
     * Test that must-have misses are correctly identified.
     */
    public function test_must_have_misses_are_tracked(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 2, requirementType: 'must_have', importance: 3),
            new VacancyTagDTO(tagId: 3, requirementType: 'must_have', importance: 3),
        ];

        $student = [
            new StudentTagDTO(tagId: 1, weight: 3),
        ];

        $result = $this->service->score($student, $vacancyTags);

        $this->assertCount(2, $result->mustHaveMisses);
        $this->assertContains(2, $result->mustHaveMisses);
        $this->assertContains(3, $result->mustHaveMisses);
    }

    /**
     * Test that scores increase with weight until clamping at 100.
     */
    public function test_scores_increase_with_weight(): void
    {
        $vacancyTags = [
            new VacancyTagDTO(tagId: 1, requirementType: 'must_have', importance: 1),
        ];

        // Weight 1: score should be 92
        $student1 = [new StudentTagDTO(tagId: 1, weight: 1)];
        $score1 = $this->service->score($student1, $vacancyTags)->score;
        $this->assertEquals(92, $score1);

        // Weight 2: score should be 96
        $student2 = [new StudentTagDTO(tagId: 1, weight: 2)];
        $score2 = $this->service->score($student2, $vacancyTags)->score;
        $this->assertEquals(96, $score2);

        // Weight 3: score should be 100
        $student3 = [new StudentTagDTO(tagId: 1, weight: 3)];
        $score3 = $this->service->score($student3, $vacancyTags)->score;
        $this->assertEquals(100, $score3);

        // Verify progression
        $this->assertGreaterThan($score1, $score2);
        $this->assertGreaterThan($score2, $score3);
    }
}

