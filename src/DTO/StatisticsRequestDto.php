<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StatisticsRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Group by parameter is required')]
        #[Assert\Choice(choices: ['day', 'month', 'year'], message: 'Group by must be one of: day, month, year')]
        public string $groupBy,

        #[Assert\Type('int')]
        #[Assert\Positive(message: 'Page must be a positive integer')]
        public int $page = 1,

        #[Assert\Type('int')]
        #[Assert\Positive(message: 'Limit must be a positive integer')]
        #[Assert\LessThanOrEqual(100, message: 'Limit cannot exceed 100')]
        public int $limit = 20,
    ) {
    }
}
