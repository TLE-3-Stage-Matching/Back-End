<?php

declare(strict_types=1);

namespace App\Matching;

use App\Matching\DTOs\StudentTagDTO;
use App\Matching\DTOs\VacancyTagDTO;
use App\Models\StudentTag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;

class StudentMatchDataLoader
{
    /**
     * Load active student tags for a given student.
     *
     * @return StudentTagDTO[]
     */
    public function loadStudentTags(int $studentUserId): array
    {
        $studentTags = StudentTag::where('student_user_id', $studentUserId)
            ->where('is_active', true)
            ->get();

        return $studentTags->map(function (StudentTag $tag) {
            return new StudentTagDTO(
                tagId: $tag->tag_id,
                weight: $tag->weight,
            );
        })->all();
    }

    /**
     * Build an effective student tag set for sandbox matching:
     * - Keep the student's real active tags EXCEPT skill/trait
     * - Replace skill/trait with sandbox-provided tags (not persisted)
     *
     * @param  StudentTagDTO[]  $sandboxSkillTraitTags
     * @return StudentTagDTO[]
     */
    public function loadEffectiveStudentTagsForSandbox(int $studentUserId, array $sandboxSkillTraitTags): array
    {
        if ($sandboxSkillTraitTags === []) {
            return $this->loadStudentTags($studentUserId);
        }

        $rows = StudentTag::query()
            ->where('student_user_id', $studentUserId)
            ->where('is_active', true)
            ->with('tag:id,tag_type')
            ->get();

        /** @var array<int, int> $weightsByTagId */
        $weightsByTagId = [];

        foreach ($rows as $row) {
            $tagType = $row->tag?->tag_type;
            if (in_array($tagType, ['skill', 'trait'], true)) {
                continue;
            }
            $weightsByTagId[(int) $row->tag_id] = (int) ($row->weight ?? (int) config('matching.default_student_weight', 3));
        }

        // Sandbox overrides (skill/trait only, validated upstream)
        foreach ($sandboxSkillTraitTags as $dto) {
            if ($dto instanceof StudentTagDTO) {
                $weightsByTagId[$dto->tagId] = $dto->weight;
            }
        }

        $result = [];
        foreach ($weightsByTagId as $tagId => $weight) {
            $result[] = new StudentTagDTO(tagId: (int) $tagId, weight: (int) $weight);
        }

        return $result;
    }

    /**
     * Load vacancy requirements (tags) for a given vacancy.
     *
     * @return VacancyTagDTO[]
     */
    public function loadVacancyTags(int $vacancyId): array
    {
        $requirements = VacancyRequirement::where('vacancy_id', $vacancyId)
            ->get();

        return $requirements->map(function (VacancyRequirement $req) {
            return new VacancyTagDTO(
                tagId: $req->tag_id,
                requirementType: $req->requirement_type,
                importance: $req->importance ?? (int) config('matching.default_vacancy_importance', 3),
            );
        })->all();
    }

    /**
     * Load all open vacancies with their tags in a single batch.
     *
     * @return array<int, VacancyTagDTO[]> Keyed by vacancy_id
     */
    public function loadOpenVacanciesWithTags(): array
    {
        // Get all vacancies from active (coordinator-approved) companies.
        // Mirrors the same logic used by the public vacancy listing (VacancyController).
        $vacancies = Vacancy::whereHas('company', fn ($q) => $q->active())->get();

        if ($vacancies->isEmpty()) {
            return [];
        }

        $vacancyIds = $vacancies->pluck('id')->all();

        // Load all requirements for these vacancies at once
        $requirements = VacancyRequirement::whereIn('vacancy_id', $vacancyIds)
            ->get();

        // Group by vacancy_id
        $result = [];
        foreach ($vacancyIds as $id) {
            $result[$id] = [];
        }

        foreach ($requirements as $req) {
            $vacancyId = $req->vacancy_id;
            if (!isset($result[$vacancyId])) {
                $result[$vacancyId] = [];
            }
            $result[$vacancyId][] = new VacancyTagDTO(
                tagId: $req->tag_id,
                requirementType: $req->requirement_type,
                importance: $req->importance ?? (int) config('matching.default_vacancy_importance', 3),
            );
        }

        return $result;
    }

    /**
     * Get vacancy details (id, title, description, company_id, company_name, tags) for a list of vacancy IDs.
     *
     * @param  int[]  $vacancyIds
     * @return array<int, array{id: int, title: string, description: string|null, company_id: int, company_name: string, tags: list<array{id: int, name: string, tag_type: string, requirement_type: string, importance: int|null}>}>
     */
    public function loadVacancyDetails(array $vacancyIds): array
    {
        if (empty($vacancyIds)) {
            return [];
        }

        $vacancies = Vacancy::whereIn('id', $vacancyIds)
            ->with(['company', 'vacancyRequirements.tag'])
            ->get();

        $result = [];
        foreach ($vacancies as $vacancy) {
            $tags = [];
            foreach ($vacancy->vacancyRequirements ?? [] as $req) {
                $tag = $req->tag;
                if ($tag) {
                    $tags[] = [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'tag_type' => $tag->tag_type,
                        'requirement_type' => $req->requirement_type,
                        'importance' => $req->importance,
                    ];
                }
            }
            $result[$vacancy->id] = [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'description' => $vacancy->description,
                'company_id' => $vacancy->company_id,
                'company_name' => $vacancy->company?->name ?? 'Unknown',
                'tags' => $tags,
            ];
        }

        return $result;
    }
}

