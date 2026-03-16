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
                importance: $req->importance,
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
                importance: $req->importance,
            );
        }

        return $result;
    }

    /**
     * Get vacancy details (id, title, company_id) for a list of vacancy IDs.
     *
     * @param  int[]  $vacancyIds
     * @return array<int, array{id: int, title: string, company_id: int, company_name: string}>
     */
    public function loadVacancyDetails(array $vacancyIds): array
    {
        if (empty($vacancyIds)) {
            return [];
        }

        $vacancies = Vacancy::whereIn('id', $vacancyIds)
            ->with('company')
            ->get();

        $result = [];
        foreach ($vacancies as $vacancy) {
            $result[$vacancy->id] = [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'company_id' => $vacancy->company_id,
                'company_name' => $vacancy->company?->name ?? 'Unknown',
            ];
        }

        return $result;
    }
}

