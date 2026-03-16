<?php

declare(strict_types=1);

namespace App\Matching\DTOs;

readonly class VacancyTagDTO
{
    public function __construct(
        public int $tagId,
        public string $requirementType,
        public int $importance,
    ) {}
}

