<?php

declare(strict_types=1);

namespace App\Matching\DTOs;

readonly class StudentTagDTO
{
    public function __construct(
        public int $tagId,
        public int $weight,
    ) {}
}

