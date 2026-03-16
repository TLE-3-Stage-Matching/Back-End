<?php

namespace App\Services;

use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentVacancyTagMatchService
{
    /** @var array<string, int|null> */
    protected array $tagTypeLimits;

    protected int $defaultStudentWeight;

    protected int $defaultVacancyImportance;

    /** @var list<string> */
    protected array $subscoreCategories;

    /** @var list<string> */
    protected array $matchTagTypes;

    public function __construct()
    {
        $this->tagTypeLimits = config('matching.tag_type_limits', []);
        $this->defaultStudentWeight = (int) config('matching.default_student_weight', 1);
        $this->defaultVacancyImportance = (int) config('matching.default_vacancy_importance', 1);
        $this->subscoreCategories = config('matching.subscore_categories', ['skill', 'trait']);
        $this->matchTagTypes = config('matching.match_tag_types', ['skill', 'trait']);
    }

    /**
     * Build student vector [tag_id => weight] (after per-type limits).
     * If $tagType is set, only include tags of that type.
     *
     * @return array<int, float>
     */
    public function buildStudentVector(int $studentUserId, ?string $tagType = null): array
    {
        $items = $this->getStudentTagItems($studentUserId);
        if ($tagType !== null) {
            $items = array_filter($items, fn ($i) => $i['tag_type'] === $tagType);
        } else {
            $items = $this->filterItemsByMatchTypes($items);
            $items = $this->applyTagTypeLimits($items, 'weight');
        }
        $vec = [];
        foreach ($items as $item) {
            $vec[$item['tag_id']] = (float) ($item['weight'] ?? $this->defaultStudentWeight);
        }
        return $vec;
    }

    /**
     * Build vacancy vector [tag_id => importance] (after per-type limits).
     * If $tagType is set, only include tags of that type.
     *
     * @return array<int, float>
     */
    public function buildVacancyVector(int $vacancyId, ?string $tagType = null): array
    {
        $items = $this->getVacancyRequirementItems($vacancyId);
        if ($tagType !== null) {
            $items = array_filter($items, fn ($i) => $i['tag_type'] === $tagType);
        } else {
            $items = $this->filterItemsByMatchTypes($items);
            $items = $this->applyTagTypeLimits($items, 'importance');
        }
        $vec = [];
        foreach ($items as $item) {
            $vec[$item['tag_id']] = (float) ($item['importance'] ?? $this->defaultVacancyImportance);
        }
        return $vec;
    }

    /**
     * Cosine similarity of two vectors, scaled 0–100.
     * Returns 0 if either vector is empty.
     *
     * @param  array<int, float>  $vecA
     * @param  array<int, float>  $vecB
     */
    public function cosineScore(array $vecA, array $vecB): float
    {
        $allIds = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));
        if ($allIds === []) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        foreach ($allIds as $id) {
            $a = $vecA[$id] ?? 0.0;
            $b = $vecB[$id] ?? 0.0;
            $dot += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }
        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }
        $cos = $dot / (sqrt($normA) * sqrt($normB));
        return round(100.0 * $cos, 1);
    }

    /**
     * Vacancy-centric cosine: restrict student vector to vacancy's tag ids so that
     * having more tags than the vacancy does not lower the score. Score is 0–100.
     *
     * @param  array<int, float>  $studentVec  Student weights keyed by tag_id
     * @param  array<int, float>  $vacancyVec  Vacancy importance keyed by tag_id
     */
    public function cosineScoreVacancyCentric(array $studentVec, array $vacancyVec): float
    {
        $studentRestricted = array_intersect_key($studentVec, $vacancyVec);
        return $this->cosineScore($studentRestricted, $vacancyVec);
    }

    /**
     * Overall match score (0–100) for one student–vacancy pair.
     */
    public function score(int $studentUserId, int $vacancyId): float
    {
        $studentVec = $this->buildStudentVector($studentUserId);
        $vacancyVec = $this->buildVacancyVector($vacancyId);
        return $this->cosineScoreVacancyCentric($studentVec, $vacancyVec);
    }

    /**
     * Match score with per-category subscores and short explanations.
     *
     * @return array{overall: float, subscores: array<string, array{score: float, explanation: string}>}
     */
    public function scoreWithSubscores(int $studentUserId, int $vacancyId): array
    {
        $overall = $this->score($studentUserId, $vacancyId);
        $subscores = [];
        $studentItemsByType = $this->groupItemsByType($this->getStudentTagItems($studentUserId));
        $vacancyItemsByType = $this->groupItemsByType($this->getVacancyRequirementItems($vacancyId));

        foreach ($this->subscoreCategories as $category) {
            $studentVec = $this->buildStudentVector($studentUserId, $category);
            $vacancyVec = $this->buildVacancyVector($vacancyId, $category);
            $score = $this->cosineScoreVacancyCentric($studentVec, $vacancyVec);
            $studentItems = $this->applyTagTypeLimits($studentItemsByType[$category] ?? [], 'weight');
            $vacancyItems = $this->applyTagTypeLimits($vacancyItemsByType[$category] ?? [], 'importance');
            $explanation = $this->buildExplanation($category, $score, $studentItems, $vacancyItems, $studentVec, $vacancyVec);
            $subscores[$category] = ['score' => $score, 'explanation' => $explanation];
        }

        return [
            'overall' => $overall,
            'subscores' => $subscores,
        ];
    }

    /**
     * Bulk scores for one student and many vacancies.
     *
     * @param  array<int, int>  $vacancyIds
     * @return array<int, float> vacancy_id => score
     */
    public function scoresForStudent(int $studentUserId, array $vacancyIds): array
    {
        if ($vacancyIds === []) {
            return [];
        }
        $studentVec = $this->buildStudentVector($studentUserId);
        $scores = [];
        $requirements = VacancyRequirement::query()
            ->whereIn('vacancy_id', $vacancyIds)
            ->with('tag:id,name,tag_type')
            ->get();
        $byVacancy = $requirements->groupBy('vacancy_id');
        foreach ($vacancyIds as $vid) {
            $items = $this->vacancyRequirementsToItems($byVacancy->get($vid, collect()));
            $items = $this->filterItemsByMatchTypes($items);
            $items = $this->applyTagTypeLimits($items, 'importance');
            $vacancyVec = [];
            foreach ($items as $item) {
                $vacancyVec[$item['tag_id']] = (float) ($item['importance'] ?? $this->defaultVacancyImportance);
            }
            $scores[$vid] = $this->cosineScoreVacancyCentric($studentVec, $vacancyVec);
        }
        return $scores;
    }

    /**
     * Vacancies from active companies with scores and subscores, sorted by score descending, paginated.
     * If $industryTagId is set, only vacancies from companies with that industry_tag_id are included.
     */
    public function vacanciesWithScoresForStudent(int $studentUserId, int $perPage = 15, int $page = 1, ?int $industryTagId = null): LengthAwarePaginator
    {
        $vacancyQuery = Vacancy::query()
            ->whereHas('company', function ($q) use ($industryTagId) {
                $q->active();
                if ($industryTagId !== null) {
                    $q->where('industry_tag_id', $industryTagId);
                }
            })
            ->with(['company:id,name,industry_tag_id', 'vacancyRequirements.tag:id,name,tag_type']);

        $studentItems = $this->getStudentTagItems($studentUserId);

        $allVacancies = $vacancyQuery->get();

        $scored = [];
        foreach ($allVacancies as $vacancy) {
            $vacancyItems = $this->vacancyRequirementsToItems($vacancy->vacancyRequirements);
            $result = $this->scoreWithSubscoresFromItems($studentItems, $vacancy->id, $vacancyItems);
            $scored[] = [
                'vacancy' => $vacancy,
                'match_score' => $result['overall'],
                'subscores' => $result['subscores'],
            ];
        }
        // Sort by match score high to low (descending)
        usort($scored, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);
        $total = count($scored);
        $slice = array_slice($scored, ($page - 1) * $perPage, $perPage);

        $path = request()->url() ?? '';
        $query = request()->query() ?? [];

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $path, 'query' => $query]
        );
    }

    /**
     * Same as scoreWithSubscores but using pre-loaded items (avoids N+1).
     *
     * @param  list<array{tag_id: int, weight: int|null, tag_type: string, name: string}>  $studentItems
     * @param  list<array{tag_id: int, importance: int|null, tag_type: string, name: string}>  $vacancyItems
     * @return array{overall: float, subscores: array<string, array{score: float, explanation: string}>}
     */
    protected function scoreWithSubscoresFromItems(array $studentItems, int $vacancyId, array $vacancyItems): array
    {
        $studentForScore = $this->filterItemsByMatchTypes($studentItems);
        $vacancyForScore = $this->filterItemsByMatchTypes($vacancyItems);
        $studentLimited = $this->applyTagTypeLimits($studentForScore, 'weight');
        $vacancyLimited = $this->applyTagTypeLimits($vacancyForScore, 'importance');
        $studentVec = $this->itemsToVector($studentLimited, 'weight', $this->defaultStudentWeight);
        $vacancyVec = $this->itemsToVector($vacancyLimited, 'importance', $this->defaultVacancyImportance);
        $overall = $this->cosineScoreVacancyCentric($studentVec, $vacancyVec);

        $subscores = [];
        $studentByType = $this->groupItemsByType($studentItems);
        $vacancyByType = $this->groupItemsByType($vacancyItems);

        foreach ($this->subscoreCategories as $category) {
            $sItems = $this->applyTagTypeLimits($studentByType[$category] ?? [], 'weight');
            $vItems = $this->applyTagTypeLimits($vacancyByType[$category] ?? [], 'importance');
            $sVec = $this->itemsToVector($sItems, 'weight', $this->defaultStudentWeight);
            $vVec = $this->itemsToVector($vItems, 'importance', $this->defaultVacancyImportance);
            $score = $this->cosineScoreVacancyCentric($sVec, $vVec);
            $explanation = $this->buildExplanation($category, $score, $sItems, $vItems, $sVec, $vVec);
            $subscores[$category] = ['score' => $score, 'explanation' => $explanation];
        }

        return [
            'overall' => $overall,
            'subscores' => $subscores,
        ];
    }

    /**
     * @param  list<array{tag_id: int, ...}>  $items
     * @param  'weight'|'importance'  $valueKey
     * @return array<int, float>
     */
    protected function itemsToVector(array $items, string $valueKey, float $default): array
    {
        $vec = [];
        foreach ($items as $item) {
            $vec[$item['tag_id']] = (float) ($item[$valueKey] ?? $default);
        }
        return $vec;
    }

    /**
     * @return list<array{tag_id: int, weight: int|null, tag_type: string, name: string}>
     */
    protected function getStudentTagItems(int $studentUserId): array
    {
        $rows = StudentTag::query()
            ->where('student_tags.student_user_id', $studentUserId)
            ->where(function ($q) {
                $q->where('student_tags.is_active', true)->orWhereNull('student_tags.is_active');
            })
            ->join('tags', 'student_tags.tag_id', '=', 'tags.id')
            ->select('student_tags.tag_id', 'student_tags.weight', 'tags.tag_type', 'tags.name')
            ->get();
        return $rows->map(fn ($r) => [
            'tag_id' => (int) $r->tag_id,
            'weight' => $r->weight !== null ? (int) $r->weight : $this->defaultStudentWeight,
            'tag_type' => $r->tag_type,
            'name' => $r->name,
        ])->values()->all();
    }

    /**
     * @return list<array{tag_id: int, importance: int|null, tag_type: string, name: string}>
     */
    protected function getVacancyRequirementItems(int $vacancyId): array
    {
        $rows = VacancyRequirement::query()
            ->where('vacancy_id', $vacancyId)
            ->join('tags', 'vacancy_requirements.tag_id', '=', 'tags.id')
            ->select('vacancy_requirements.tag_id', 'vacancy_requirements.importance', 'tags.tag_type', 'tags.name')
            ->get();
        return $rows->map(fn ($r) => [
            'tag_id' => (int) $r->tag_id,
            'importance' => $r->importance !== null ? (int) $r->importance : $this->defaultVacancyImportance,
            'tag_type' => $r->tag_type,
            'name' => $r->name,
        ])->values()->all();
    }

    /**
     * @param  array<int, float>  $studentVec
     * @param  array<int, float>  $vacancyVec
     * @param  list<array{tag_id: int, name: string, ...}>  $studentItems
     * @param  list<array{tag_id: int, name: string, ...}>  $vacancyItems
     */
    protected function buildExplanation(string $category, float $score, array $studentItems, array $vacancyItems, array $studentVec, array $vacancyVec): string
    {
        $label = ucfirst($category) . ' match';
        $vacancyCount = count($vacancyItems);
        $studentCount = count($studentItems);
        $matchingIds = array_intersect(array_keys($studentVec), array_keys($vacancyVec));
        $matchCount = count($matchingIds);

        if ($vacancyCount === 0) {
            return "{$label}: " . round($score) . "% — No {$category} tags specified for this vacancy.";
        }
        if ($studentCount === 0) {
            return "{$label}: " . round($score) . "% — You have no {$category} tags in your profile.";
        }

        $tagIdToName = [];
        foreach ($vacancyItems as $item) {
            $tagIdToName[$item['tag_id']] = $item['name'] ?? '';
        }
        foreach ($studentItems as $item) {
            if (! isset($tagIdToName[$item['tag_id']])) {
                $tagIdToName[$item['tag_id']] = $item['name'] ?? '';
            }
        }

        $parts = [];
        $parts[] = sprintf('%s: %s%% — %d of %d required %s match your profile.', $label, (string) round($score), $matchCount, $vacancyCount, $category === 'skill' ? 'skills' : $category . 's');

        if ($matchCount > 0) {
            $weightDetails = [];
            foreach ($matchingIds as $tagId) {
                $name = $tagIdToName[$tagId] ?? 'Tag #' . $tagId;
                $yourWeight = $studentVec[$tagId] ?? 0;
                $vacancyImportance = $vacancyVec[$tagId] ?? 0;
                $weightDetails[] = sprintf('%s (your weight %s, vacancy importance %s)', $name, $this->formatWeight($yourWeight), $this->formatWeight($vacancyImportance));
            }
            $parts[] = ' Matching tags and how they’re weighted: ' . implode('; ', array_slice($weightDetails, 0, 8)) . (count($weightDetails) > 8 ? '…' : '') . '.';
        }

        $parts[] = ' The score uses cosine similarity: it measures how well your tag strengths align with the vacancy’s requirements. Each matching tag contributes (your weight × vacancy importance) to the numerator; the denominator depends on the overall magnitude of your matching weights and the vacancy’s requirements. So a few tags with high weight on your side and high importance for the vacancy can yield a higher percentage than the raw match count (e.g. 2 of 6).';

        return implode('', $parts);
    }

    private function formatWeight(float $value): string
    {
        return $value == (float) (int) $value ? (string) (int) $value : (string) round($value, 1);
    }

    /**
     * Keep only items whose tag_type is in match_tag_types (for scoring).
     *
     * @param  list<array{tag_type: string, ...}>  $items
     * @return list<array{tag_type: string, ...}>
     */
    protected function filterItemsByMatchTypes(array $items): array
    {
        $types = array_flip($this->matchTagTypes);
        return array_values(array_filter($items, fn ($i) => isset($types[$i['tag_type'] ?? ''])));
    }

    /**
     * Return the single major tag_id for the student, or null if none or more than one.
     *
     * @param  list<array{tag_id: int, tag_type: string, ...}>  $studentItems
     */
    protected function getStudentMajorTagId(array $studentItems): ?int
    {
        $majorItems = array_filter($studentItems, fn ($i) => ($i['tag_type'] ?? '') === 'major');
        if (count($majorItems) !== 1) {
            return null;
        }
        $first = reset($majorItems);

        return (int) $first['tag_id'];
    }

    /**
     * @param  list<array{tag_type: string, ...}>  $items
     * @param  'weight'|'importance'  $sortKey
     * @return list<array{tag_type: string, ...}>
     */
    protected function applyTagTypeLimits(array $items, string $sortKey): array
    {
        if ($this->tagTypeLimits === []) {
            return $items;
        }
        $byType = [];
        foreach ($items as $item) {
            $type = $item['tag_type'] ?? 'skill';
            $byType[$type][] = $item;
        }
        $out = [];
        foreach ($byType as $type => $list) {
            $limit = $this->tagTypeLimits[$type] ?? null;
            if ($limit === null) {
                $out = array_merge($out, $list);
                continue;
            }
            usort($list, fn ($a, $b) => (($b[$sortKey] ?? 0) <=> ($a[$sortKey] ?? 0)));
            $out = array_merge($out, array_slice($list, 0, $limit));
        }
        return $out;
    }

    /**
     * @param  list<array{tag_type: string, ...}>  $items
     * @return array<string, list<array{tag_type: string, ...}>>
     */
    protected function groupItemsByType(array $items): array
    {
        $out = [];
        foreach ($this->subscoreCategories as $c) {
            $out[$c] = [];
        }
        foreach ($items as $item) {
            $type = $item['tag_type'] ?? 'skill';
            if (! isset($out[$type])) {
                $out[$type] = [];
            }
            $out[$type][] = $item;
        }
        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, VacancyRequirement>  $requirements
     * @return list<array{tag_id: int, importance: int|null, tag_type: string, name: string}>
     */
    protected function vacancyRequirementsToItems($requirements): array
    {
        $items = [];
        foreach ($requirements as $r) {
            $tag = $r->relationLoaded('tag') ? $r->tag : null;
            $items[] = [
                'tag_id' => (int) $r->tag_id,
                'importance' => $r->importance !== null ? (int) $r->importance : $this->defaultVacancyImportance,
                'tag_type' => $tag ? $tag->tag_type : 'skill',
                'name' => $tag ? $tag->name : '',
            ];
        }
        return $items;
    }
}
