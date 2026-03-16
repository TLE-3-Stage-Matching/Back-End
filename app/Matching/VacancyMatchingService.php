<?php

declare(strict_types=1);

namespace App\Matching;

use App\Matching\DTOs\CriteriaConfigDTO;
use App\Matching\DTOs\MatchResultDTO;
use App\Matching\DTOs\StudentTagDTO;
use App\Matching\DTOs\VacancyTagDTO;

class VacancyMatchingService
{
    public function __construct(
        private readonly CriteriaConfigDTO $config = new CriteriaConfigDTO(),
    ) {}

    /**
     * Score a single vacancy for a student.
     *
     * @param  StudentTagDTO[]  $studentTags Student's active tags with weights
     * @param  VacancyTagDTO[]  $vacancyTags Vacancy requirements with types and importances
     */
    public function score(array $studentTags, array $vacancyTags): MatchResultDTO
    {
        // Build lookup maps for O(1) access
        $studentMap = [];
        foreach ($studentTags as $tag) {
            $studentMap[$tag->tagId] = $tag->weight;
        }

        // Separate vacancy tags by requirement type
        $mustHaves = [];
        $niceToHaves = [];
        foreach ($vacancyTags as $tag) {
            if ($tag->requirementType === 'must_have') {
                $mustHaves[$tag->tagId] = $tag->importance;
            } else {
                $niceToHaves[$tag->tagId] = $tag->importance;
            }
        }

        // Step 1-2: Calculate individual tag match scores and normalized importances
        $mustHaveScores = [];
        $mustHaveImportances = [];
        $mustHaveMissing = [];

        foreach ($mustHaves as $tagId => $importance) {
            $matchScore = $this->calculateTagMatchScore($studentMap[$tagId] ?? null);
            if ($matchScore === 0.0 && !isset($studentMap[$tagId])) {
                $mustHaveMissing[] = $tagId;
            }
            $mustHaveScores[$tagId] = $matchScore;
            $mustHaveImportances[$tagId] = $importance / 5.0; // Normalized
        }

        $niceToHaveScores = [];
        $niceToHaveImportances = [];

        foreach ($niceToHaves as $tagId => $importance) {
            $matchScore = $this->calculateTagMatchScore($studentMap[$tagId] ?? null);
            $niceToHaveScores[$tagId] = $matchScore;
            $niceToHaveImportances[$tagId] = $importance / 5.0; // Normalized
        }

        // Step 3: Must-have sub-score
        $sMh = $this->calculateSubScore($mustHaveScores, $mustHaveImportances);

        // Step 4: Nice-to-have sub-score
        $sNth = $this->calculateSubScore($niceToHaveScores, $niceToHaveImportances);

        // Step 5: Combined tag score
        $sTags = (0.8 * $sMh) + (0.2 * $sNth);

        // Step 6: Must-have penalty
        $nMiss = count($mustHaveMissing);
        $nTotal = count($mustHaves);
        $penalty = ($nTotal > 0)
            ? ($nMiss / $nTotal) * $this->config->penaltyMax
            : 0.0;

        // Step 7: Final score
        $raw = $sTags - $penalty;
        $finalScore = (int) round(max(0, min(100, $raw * 100)));

        return new MatchResultDTO(
            vacancyId: 0, // Will be set by caller
            score: $finalScore,
            mustHaveMisses: $mustHaveMissing,
            dimensionDetail: [
                's_mh' => round($sMh, 3),
                's_nth' => round($sNth, 3),
                's_tags' => round($sTags, 3),
                'penalty' => round($penalty, 3),
            ],
        );
    }

    /**
     * Rank multiple vacancies for a student by score (descending).
     *
     * @param  StudentTagDTO[]  $studentTags
     * @param  array<int, VacancyTagDTO[]>  $vacanciesByTagSets Keyed by vacancy_id
     * @return MatchResultDTO[]
     */
    public function rankForStudent(array $studentTags, array $vacanciesByTagSets): array
    {
        $results = [];

        foreach ($vacanciesByTagSets as $vacancyId => $vacancyTags) {
            $result = $this->score($studentTags, $vacancyTags);
            $results[] = new MatchResultDTO(
                vacancyId: $vacancyId,
                score: $result->score,
                mustHaveMisses: $result->mustHaveMisses,
                dimensionDetail: $result->dimensionDetail,
            );
        }

        // Sort by score descending
        usort($results, fn (MatchResultDTO $a, MatchResultDTO $b) => $b->score <=> $a->score);

        return $results;
    }

    /**
     * Step 1: Individual tag match score.
     *
     * If student has the tag: m_k = 1 + (w_k - 3) / 20
     * If not: m_k = 0
     */
    private function calculateTagMatchScore(?int $studentWeight): float
    {
        if ($studentWeight === null) {
            return 0.0;
        }

        return 1.0 + ($studentWeight - 3) / 20.0;
    }

    /**
     * Step 3/4: Calculate sub-score from match scores and normalized importances.
     *
     * @param  array<int, float>  $scores Match scores keyed by tag_id
     * @param  array<int, float>  $importances Normalized importances (0.2–1.0) keyed by tag_id
     */
    private function calculateSubScore(array $scores, array $importances): float
    {
        if (empty($scores)) {
            return 1.0;
        }

        $numerator = 0.0;
        $denominator = 0.0;

        foreach ($scores as $tagId => $score) {
            $importance = $importances[$tagId] ?? 0.2;
            $numerator += $importance * $score;
            $denominator += $importance;
        }

        if ($denominator === 0.0) {
            return 1.0;
        }

        return $numerator / $denominator;
    }
}

