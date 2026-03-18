<?php

declare(strict_types=1);

namespace App\Matching\DTOs;

readonly class CriteriaConfigDTO
{
    public function __construct(
        public float $mustHaveWeight = 0.8,
        public float $niceToHaveWeight = 0.2,
        public float $penaltyMax = 0.25,
    ) {}
}

