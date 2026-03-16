<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
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
            return response()->json(['data' => []]);
        }

        // Score all vacancies
        $scores = $this->matchService->rankForStudent($studentTags, $vacanciesWithTags);

        // Get top 3
        $topThree = array_slice($scores, 0, 3);

        // Load vacancy details
        $vacancyIds = array_map(fn ($result) => $result->vacancyId, $topThree);
        $vacancyDetails = $this->dataLoader->loadVacancyDetails($vacancyIds);

        // Format response
        $data = [];
        foreach ($topThree as $result) {
            $details = $vacancyDetails[$result->vacancyId] ?? null;
            if ($details) {
                $data[] = [
                    'vacancy_id' => $result->vacancyId,
                    'title' => $details['title'],
                    'company' => $details['company_name'],
                    'score' => $result->score,
                ];
            }
        }

        return response()->json(['data' => $data]);
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
            return response()->json(['data' => []]);
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
                $data[] = [
                    'vacancy_id' => $result->vacancyId,
                    'title' => $details['title'],
                    'company' => $details['company_name'],
                    'score' => $result->score,
                    'must_have_misses' => $result->mustHaveMisses,
                    'breakdown' => [
                        's_mh' => $result->dimensionDetail['s_mh'] ?? 0.0,
                        's_nth' => $result->dimensionDetail['s_nth'] ?? 0.0,
                        's_tags' => $result->dimensionDetail['s_tags'] ?? 0.0,
                        'penalty' => $result->dimensionDetail['penalty'] ?? 0.0,
                    ],
                ];
            }
        }

        return response()->json(['data' => $data]);
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

        $vacancy->load('company:id,name');

        return response()->json([
            'data' => [
                'vacancy' => [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'company' => $vacancy->company?->name,
                ],
                'score' => $result->score,
                'breakdown' => [
                    's_mh' => $result->dimensionDetail['s_mh'] ?? 0.0,
                    's_nth' => $result->dimensionDetail['s_nth'] ?? 0.0,
                    's_tags' => $result->dimensionDetail['s_tags'] ?? 0.0,
                    'penalty' => $result->dimensionDetail['penalty'] ?? 0.0,
                ],
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
}
