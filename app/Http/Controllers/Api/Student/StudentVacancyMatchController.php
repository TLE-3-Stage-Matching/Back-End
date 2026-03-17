<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Matching\DTOs\StudentTagDTO;
use App\Matching\DTOs\VacancyTagDTO;
use App\Matching\StudentMatchDataLoader;
use App\Matching\VacancyMatchingService;
use App\Models\Tag;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;

class StudentVacancyMatchController extends Controller
{
    public function __construct(
        private readonly VacancyMatchingService $matchService,
        private readonly StudentMatchDataLoader $dataLoader,
    ) {}

    /**
     * GET /api/v1/student/vacancies/top-matches
     *
     * Score all open vacancies for the authenticated student and return top 3.
     */
    public function topMatches(): JsonResponse
    {
        $studentUserId = (int) auth()->user()->id;

        // Load student tags
        $studentTags = $this->dataLoader->loadStudentTags($studentUserId);

        // Load all open vacancies with their tags
        $vacanciesWithTags = $this->dataLoader->loadOpenVacanciesWithTags();

        if (empty($vacanciesWithTags)) {
            return response()->json([
                'data' => [],
                'algorithm_transparency' => [
                    'in_simple_terms' => [
                        'Your tags are compared with vacancy tags.',
                        'Must-have tags count most, nice-to-have tags count less.',
                        'Missing must-have tags reduce your score with a penalty.',
                    ],
                ],
            ]);
        }

        // Score all vacancies
        $scores = $this->matchService->rankForStudent($studentTags, $vacanciesWithTags);

        // Get top 3
        $topThree = array_slice($scores, 0, 3);

        // Collect relevant tag IDs so we can return readable tag names.
        $relevantTagIds = [];
        foreach ($topThree as $result) {
            foreach ($result->mustHaveMisses as $tagId) {
                $relevantTagIds[] = (int) $tagId;
            }
            foreach ($vacanciesWithTags[$result->vacancyId] ?? [] as $vt) {
                $relevantTagIds[] = (int) $vt->tagId;
            }
        }

        $tagNames = [];
        if ($relevantTagIds !== []) {
            $tagNames = Tag::query()
                ->whereIn('id', array_values(array_unique($relevantTagIds)))
                ->pluck('name', 'id')
                ->all();
        }

        // Load vacancy details
        $vacancyIds = array_map(fn ($result) => $result->vacancyId, $topThree);
        $vacancyDetails = $this->dataLoader->loadVacancyDetails($vacancyIds);

        // Format response
        $data = [];
        $studentWeightsByTagId = $this->buildStudentWeightMap($studentTags);
        foreach ($topThree as $result) {
            $details = $vacancyDetails[$result->vacancyId] ?? null;
            if ($details) {
                $vacancyTags = $vacanciesWithTags[$result->vacancyId] ?? [];
                $data[] = [
                    'vacancy_id' => $result->vacancyId,
                    'title' => $details['title'],
                    'company' => $details['company_name'],
                    'score' => $result->score,
                    'score_feedback' => $this->buildScoreFeedback($result->score),
                    'feedback' => $this->buildTopMatchFeedback(
                        score: $result->score,
                        mustHaveTotal: $this->countMustHaveTags($vacancyTags),
                        mustHaveMisses: $result->mustHaveMisses,
                        dimensionDetail: $result->dimensionDetail,
                        tagNames: $tagNames,
                        recommendations: $this->buildScoreImprovementRecommendations(
                            studentTags: $studentTags,
                            vacancyTags: $vacancyTags,
                            studentWeightsByTagId: $studentWeightsByTagId,
                            tagNames: $tagNames,
                        ),
                    ),
                ];
            }
        }

        return response()->json([
            'data' => $data,
            'algorithm_transparency' => [
                'in_simple_terms' => [
                    'The algorithm checks whether your tags match vacancy tags.',
                    'Must-have tags count the most, nice-to-have tags count less.',
                    'Missing must-have tags adds a penalty that lowers your final score.',
                    'Final score is converted to a 0-100 number.',
                ],
                'formula' => [
                    'must_have_weight' => '80%',
                    'nice_to_have_weight' => '20%',
                    'combined' => 'S_tags = 0.8 * S_MH + 0.2 * S_NTH',
                    'penalty' => 'P = (missing_must_haves / total_must_haves) * 0.25',
                    'final' => 'score = clamp((S_tags - P) * 100, 0, 100)',
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/student/vacancies/with-scores
     *
     * Score all open vacancies for the authenticated student and return all sorted by score descending.
     */
    public function withScores(): JsonResponse
    {
        $studentUserId = (int) auth()->user()->id;

        // Load student tags
        $studentTags = $this->dataLoader->loadStudentTags($studentUserId);

        // Load all open vacancies with their tags
        $vacanciesWithTags = $this->dataLoader->loadOpenVacanciesWithTags();

        if (empty($vacanciesWithTags)) {
            return response()->json([
                'data' => [],
                'algorithm_transparency' => [
                    'in_simple_terms' => [
                        'Your tags are compared with vacancy tags.',
                        'Must-have tags count most, nice-to-have tags count less.',
                        'Missing must-have tags reduce your score with a penalty.',
                    ],
                ],
            ]);
        }

        // Score all vacancies
        $scores = $this->matchService->rankForStudent($studentTags, $vacanciesWithTags);

        // Load vacancy details
        $vacancyIds = array_map(fn ($result) => $result->vacancyId, $scores);
        $vacancyDetails = $this->dataLoader->loadVacancyDetails($vacancyIds);

        // Format response
        $data = [];
        foreach ($scores as $result) {
            $details = $vacancyDetails[$result->vacancyId] ?? null;
            if ($details) {
                $vacancyTags = $vacanciesWithTags[$result->vacancyId] ?? [];
                $mustHaveTotal = $this->countMustHaveTags($vacancyTags);
                $mustHaveMissCount = count($result->mustHaveMisses);
                $data[] = [
                    'vacancy_id' => $result->vacancyId,
                    'title' => $details['title'],
                    'company' => $details['company_name'],
                    'score' => $result->score,
                    'score_feedback' => $this->buildScoreFeedback($result->score),
                    'must_have_misses' => $result->mustHaveMisses,
                    'breakdown' => [
                        's_mh' => $result->dimensionDetail['s_mh'] ?? 0.0,
                        's_nth' => $result->dimensionDetail['s_nth'] ?? 0.0,
                        's_tags' => $result->dimensionDetail['s_tags'] ?? 0.0,
                        'penalty' => $result->dimensionDetail['penalty'] ?? 0.0,
                    ],
                    'human_explanation' => [
                        'summary' => $this->buildStudentSummary(
                            score: $result->score,
                            mustHaveTotal: $mustHaveTotal,
                            mustHaveMatched: max(0, $mustHaveTotal - $mustHaveMissCount),
                            mustHaveMissed: $mustHaveMissCount,
                            niceToHaveMatched: 0,
                            niceToHaveTotal: $this->countNiceToHaveTags($vacancyTags),
                        ),
                        'must_have_context' => $this->buildMustHaveContext($mustHaveTotal, $mustHaveMissCount),
                        'how_score_is_calculated' => [
                            'Must-have tags have 80% impact.',
                            'Nice-to-have tags have 20% impact.',
                            'Missing must-have tags reduce your score with a penalty.',
                        ],
                    ],
                ];
            }
        }

        return response()->json([
            'data' => $data,
            'algorithm_transparency' => [
                'in_simple_terms' => [
                    'Your tags are compared with vacancy tags.',
                    'Must-have tags count most, nice-to-have tags count less.',
                    'Missing must-have tags reduce your score with a penalty.',
                    'The final score is shown on a 0-100 scale.',
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/student/vacancies/{vacancy}/detail
     *
     * Explain why the score was given for a single vacancy.
     */
    public function detail(Vacancy $vacancy): JsonResponse
    {
        // Keep visibility aligned with other student/public vacancy listings.
        if (! $vacancy->company()->where('is_active', true)->exists()) {
            return response()->json(['message' => 'Vacancy not found.'], 404);
        }

        $studentUserId = (int) auth()->user()->id;

        $studentTags = $this->dataLoader->loadStudentTags($studentUserId);
        $vacancyTags = $this->dataLoader->loadVacancyTags((int) $vacancy->id);

        $result = $this->matchService->score($studentTags, $vacancyTags);

        $studentByTagId = [];
        foreach ($studentTags as $tag) {
            $studentByTagId[$tag->tagId] = $tag->weight;
        }

        $allTagIds = array_values(array_unique(array_map(fn ($t) => $t->tagId, $vacancyTags)));
        $tagNames = Tag::query()
            ->whereIn('id', $allTagIds)
            ->pluck('name', 'id')
            ->all();

        $tagDetails = [];
        $mustHaveDetails = [];
        $niceToHaveDetails = [];

        foreach ($vacancyTags as $vt) {
            $studentWeight = $studentByTagId[$vt->tagId] ?? null;
            $hasTag = $studentWeight !== null;
            $importanceNorm = $vt->importance / 5.0;
            $matchMultiplier = $hasTag ? (1 + ($studentWeight - 3) / 20.0) : 0.0;
            $weightedContribution = $importanceNorm * $matchMultiplier;

            $row = [
                'tag_id' => $vt->tagId,
                'tag_name' => $tagNames[$vt->tagId] ?? ('Tag #' . $vt->tagId),
                'requirement_type' => $vt->requirementType,
                'importance' => $vt->importance,
                'importance_normalized' => round($importanceNorm, 3),
                'student_has_tag' => $hasTag,
                'student_weight' => $studentWeight,
                'match_multiplier' => round($matchMultiplier, 3),
                'weighted_contribution' => round($weightedContribution, 3),
            ];

            $tagDetails[] = $row;
            if ($vt->requirementType === 'must_have') {
                $mustHaveDetails[] = $row;
            } else {
                $niceToHaveDetails[] = $row;
            }
        }

        $missDetails = array_values(array_map(function (int $tagId) use ($tagNames) {
            return [
                'tag_id' => $tagId,
                'tag_name' => $tagNames[$tagId] ?? ('Tag #' . $tagId),
            ];
        }, $result->mustHaveMisses));

        // Build a readable explanation for students.
        $matchedMustHave = array_values(array_filter($mustHaveDetails, fn ($t) => $t['student_has_tag']));
        $matchedNiceToHave = array_values(array_filter($niceToHaveDetails, fn ($t) => $t['student_has_tag']));
        $missingNiceToHave = array_values(array_filter($niceToHaveDetails, fn ($t) => ! $t['student_has_tag']));

        usort($matchedMustHave, fn ($a, $b) => $b['importance'] <=> $a['importance']);
        usort($matchedNiceToHave, fn ($a, $b) => $b['importance'] <=> $a['importance']);
        usort($missingNiceToHave, fn ($a, $b) => $b['importance'] <=> $a['importance']);

        $highImpactMissingMustHave = array_values(array_filter($missDetails, function ($m) use ($mustHaveDetails) {
            foreach ($mustHaveDetails as $mh) {
                if ($mh['tag_id'] === $m['tag_id']) {
                    return $mh['importance'] >= 4;
                }
            }
            return false;
        }));

        $summary = $this->buildStudentSummary(
            score: $result->score,
            mustHaveTotal: count($mustHaveDetails),
            mustHaveMatched: count($matchedMustHave),
            mustHaveMissed: count($missDetails),
            niceToHaveMatched: count($matchedNiceToHave),
            niceToHaveTotal: count($niceToHaveDetails),
        );

        $strengths = array_values(array_map(
            fn ($t) => $t['tag_name'],
            array_slice(array_merge($matchedMustHave, $matchedNiceToHave), 0, 5)
        ));

        $improveNow = array_values(array_map(
            fn ($t) => $t['tag_name'],
            array_slice(array_merge($highImpactMissingMustHave, $missingNiceToHave), 0, 5)
        ));

        $recommendations = $this->buildScoreImprovementRecommendations(
            studentTags: $studentTags,
            vacancyTags: $vacancyTags,
            studentWeightsByTagId: $studentByTagId,
            tagNames: $tagNames,
        );
        $addTagRecommendations = array_values(array_filter(
            $recommendations,
            fn (array $row) => ($row['action'] ?? null) === 'acquire_tag'
        ));
        $increaseWeightRecommendations = array_values(array_filter(
            $recommendations,
            fn (array $row) => ($row['action'] ?? null) === 'increase_weight'
        ));

        $vacancy->load('company:id,name');

        return response()->json([
            'data' => [
                'vacancy' => [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'company' => $vacancy->company?->name,
                ],
                'score' => $result->score,
                'score_feedback' => $this->buildScoreFeedback($result->score),
                'breakdown' => [
                    's_mh' => $result->dimensionDetail['s_mh'] ?? 0.0,
                    's_nth' => $result->dimensionDetail['s_nth'] ?? 0.0,
                    's_tags' => $result->dimensionDetail['s_tags'] ?? 0.0,
                    'penalty' => $result->dimensionDetail['penalty'] ?? 0.0,
                ],
                'must_have_context' => $this->buildMustHaveContext(count($mustHaveDetails), count($missDetails)),
                'must_have_misses' => $missDetails,
                'nice_to_have_misses' => array_values(array_map(fn ($t) => [
                    'tag_id' => $t['tag_id'],
                    'tag_name' => $t['tag_name'],
                    'importance' => $t['importance'],
                ], $missingNiceToHave)),
                'tags' => [
                    'all' => $tagDetails,
                    'must_haves' => $mustHaveDetails,
                    'nice_to_haves' => $niceToHaveDetails,
                ],
                'human_explanation' => [
                    'summary' => $summary,
                    'what_you_match_well' => $strengths,
                    'what_to_improve_next' => $improveNow,
                    'tips' => [
                        'Focus first on missing must-have tags, especially high-importance ones (4-5).',
                        'Add missing skills to your profile only if you can realistically perform them.',
                        'Improve existing tag weights by gaining project or internship experience in those skills.',
                    ],
                ],
                'improvement_plan' => [
                    'recommended_add_tags' => $addTagRecommendations,
                    'recommended_increase_existing_tags' => $increaseWeightRecommendations,
                ],
                'explanation' => [
                    'formula' => [
                        'match_multiplier' => 'm_k = 1 + (w_k - 3) / 20 when student has the tag, else 0',
                        'importance_normalized' => 'i_hat = importance / 5',
                        'must_have_score' => 'S_MH = weighted average of must-have tag matches (or 1.0 when none)',
                        'nice_to_have_score' => 'S_NTH = weighted average of nice-to-have tag matches (or 1.0 when none)',
                        'combined' => 'S_tags = 0.8 * S_MH + 0.2 * S_NTH',
                        'penalty' => 'P = (missing_must_haves / total_must_haves) * 0.25',
                        'final' => 'score = clamp((S_tags - P) * 100, 0, 100)',
                    ],
                ],
            ],
            'links' => [
                'self' => url("/api/v1/student/vacancies/{$vacancy->id}/detail"),
            ],
        ]);
    }

    /**
     * Create a short, student-friendly summary sentence.
     */
    private function buildStudentSummary(
        int $score,
        int $mustHaveTotal,
        int $mustHaveMatched,
        int $mustHaveMissed,
        int $niceToHaveMatched,
        int $niceToHaveTotal,
    ): string {
        if ($mustHaveTotal === 0) {
            return "You match this vacancy with a score of {$score}. There are no must-have tags, so your score mostly depends on nice-to-have overlap.";
        }

        return "You match this vacancy with a score of {$score}. You meet {$mustHaveMatched}/{$mustHaveTotal} must-have tags and {$niceToHaveMatched}/{$niceToHaveTotal} nice-to-have tags. Missing {$mustHaveMissed} must-have tag(s) lowers your score the most.";
    }

    /**
     * Build short transparency feedback for each top match card.
     *
     * @param  array<int>  $mustHaveMisses
     * @param  array<string, float>  $dimensionDetail
     * @param  array<int|string, string>  $tagNames
     * @param  array<int, array<string, mixed>>  $recommendations
     * @return array<string, mixed>
     */
    private function buildTopMatchFeedback(
        int $score,
        int $mustHaveTotal,
        array $mustHaveMisses,
        array $dimensionDetail,
        array $tagNames,
        array $recommendations,
    ): array {
        $missCount = count($mustHaveMisses);
        $missingTags = array_values(array_map(
            fn (int $tagId) => $tagNames[$tagId] ?? ('Tag #' . $tagId),
            $mustHaveMisses,
        ));

        $summary = $mustHaveTotal === 0
            ? 'This vacancy has no must-have tags, so your score is mostly based on nice-to-have overlap.'
            : ($missCount === 0
                ? 'Great fit. You currently match all must-have tags for this vacancy.'
                : "Good potential, but you are missing {$missCount} must-have tag(s), which reduces the score most.");

        return [
            'summary' => $summary,
            'must_have_context' => $this->buildMustHaveContext($mustHaveTotal, $missCount),
            'missing_must_have_tags' => $missingTags,
            'score_breakdown' => [
                'must_have_fit' => $dimensionDetail['s_mh'] ?? 0.0,
                'nice_to_have_fit' => $dimensionDetail['s_nth'] ?? 0.0,
                'combined_before_penalty' => $dimensionDetail['s_tags'] ?? 0.0,
                'penalty' => $dimensionDetail['penalty'] ?? 0.0,
                'final_score' => $score,
            ],
            'recommended_actions' => $recommendations,
        ];
    }

    /**
     * @param  StudentTagDTO[]  $studentTags
     * @return array<int, int>
     */
    private function buildStudentWeightMap(array $studentTags): array
    {
        $map = [];
        foreach ($studentTags as $tag) {
            $map[$tag->tagId] = $tag->weight;
        }

        return $map;
    }

    /**
     * Build actionable items with estimated score lift.
     *
     * @param  StudentTagDTO[]  $studentTags
     * @param  VacancyTagDTO[]  $vacancyTags
     * @param  array<int, int>  $studentWeightsByTagId
     * @param  array<int|string, string>  $tagNames
     * @return array<int, array<string, mixed>>
     */
    private function buildScoreImprovementRecommendations(
        array $studentTags,
        array $vacancyTags,
        array $studentWeightsByTagId,
        array $tagNames,
    ): array {
        if ($vacancyTags === []) {
            return [];
        }

        $baselineScore = $this->matchService->score($studentTags, $vacancyTags)->score;
        $recommendations = [];

        foreach ($vacancyTags as $vt) {
            $tagId = $vt->tagId;
            $tagName = $tagNames[$tagId] ?? ('Tag #' . $tagId);
            $currentWeight = $studentWeightsByTagId[$tagId] ?? null;

            // Option 1: acquire the tag (default weight 3) if missing.
            if ($currentWeight === null) {
                $simulatedTags = $this->addOrReplaceStudentTagWeight($studentTags, $tagId, 3);
                $newScore = $this->matchService->score($simulatedTags, $vacancyTags)->score;
                $increase = max(0, $newScore - $baselineScore);

                if ($increase > 0) {
                    $recommendations[] = [
                        'tag_id' => $tagId,
                        'tag_name' => $tagName,
                        'action' => 'acquire_tag',
                        'suggested_target_weight' => 3,
                        'estimated_score_increase' => $increase,
                        'estimated_new_score' => $newScore,
                    ];
                }
            }

            // Option 2: increase current tag weight by +1 (up to 5).
            if ($currentWeight !== null && $currentWeight < 5) {
                $targetWeight = min(5, $currentWeight + 1);
                $simulatedTags = $this->addOrReplaceStudentTagWeight($studentTags, $tagId, $targetWeight);
                $newScore = $this->matchService->score($simulatedTags, $vacancyTags)->score;
                $increase = max(0, $newScore - $baselineScore);

                if ($increase > 0) {
                    $recommendations[] = [
                        'tag_id' => $tagId,
                        'tag_name' => $tagName,
                        'action' => 'increase_weight',
                        'current_weight' => $currentWeight,
                        'suggested_target_weight' => $targetWeight,
                        'estimated_score_increase' => $increase,
                        'estimated_new_score' => $newScore,
                    ];
                }
            }
        }

        usort(
            $recommendations,
            fn (array $a, array $b) => ($b['estimated_score_increase'] <=> $a['estimated_score_increase'])
                ?: (($b['estimated_new_score'] ?? 0) <=> ($a['estimated_new_score'] ?? 0))
        );

        return array_slice($recommendations, 0, 5);
    }

    /**
     * @param  VacancyTagDTO[]  $vacancyTags
     */
    private function countMustHaveTags(array $vacancyTags): int
    {
        return count(array_filter($vacancyTags, fn (VacancyTagDTO $vt) => $vt->requirementType === 'must_have'));
    }

    /**
     * @param  VacancyTagDTO[]  $vacancyTags
     */
    private function countNiceToHaveTags(array $vacancyTags): int
    {
        return count(array_filter($vacancyTags, fn (VacancyTagDTO $vt) => $vt->requirementType !== 'must_have'));
    }

    /**
     * @return array{label: string, message: string}
     */
    private function buildScoreFeedback(int $score): array
    {
        if ($score > 80) {
            return ['label' => 'great_match', 'message' => 'Great match: your profile strongly aligns with this vacancy.'];
        }

        if ($score >= 70) {
            return ['label' => 'good_match', 'message' => 'Good match: you are close, and a few targeted improvements can raise your score.'];
        }

        if ($score < 60) {
            return ['label' => 'subpar_match', 'message' => 'Subpar match right now: focus on missing or weak tags to improve your fit.'];
        }

        return ['label' => 'fair_match', 'message' => 'Fair match: there is clear potential with focused improvements.'];
    }

    /**
     * @return array{must_have_total: int, missing_must_haves: int, message: string}
     */
    private function buildMustHaveContext(int $mustHaveTotal, int $missingMustHaves): array
    {
        if ($mustHaveTotal === 0) {
            return [
                'must_have_total' => 0,
                'missing_must_haves' => 0,
                'message' => 'No must-have tags are defined for this vacancy, so your score is mainly driven by nice-to-have tags.',
            ];
        }

        return [
            'must_have_total' => $mustHaveTotal,
            'missing_must_haves' => $missingMustHaves,
            'message' => $missingMustHaves > 0
                ? 'Missing must-have tags apply a penalty and can lower your score significantly.'
                : 'You matched all must-have tags for this vacancy, which strongly supports your score.',
        ];
    }

    /**
     * @param  StudentTagDTO[]  $studentTags
     * @return StudentTagDTO[]
     */
    private function addOrReplaceStudentTagWeight(array $studentTags, int $tagId, int $weight): array
    {
        $updated = [];
        $replaced = false;

        foreach ($studentTags as $tag) {
            if ($tag->tagId === $tagId) {
                $updated[] = new StudentTagDTO(tagId: $tagId, weight: $weight);
                $replaced = true;
                continue;
            }

            $updated[] = $tag;
        }

        if (! $replaced) {
            $updated[] = new StudentTagDTO(tagId: $tagId, weight: $weight);
        }

        return $updated;
    }
}
