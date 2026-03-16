<?php

declare(strict_types=1);

namespace App\Matching\DTOs;

readonly class MatchResultDTO
{
    /**
     * @param array<int> $mustHaveMisses Tag IDs that are missing
     * @param array<string, float> $dimensionDetail Breakdown with keys: s_mh, s_nth, s_tags, penalty
     */
    public function __construct(
        public int $vacancyId,
        public int $score,
        public array $mustHaveMisses = [],
        public array $dimensionDetail = [],
    ) {}
}

