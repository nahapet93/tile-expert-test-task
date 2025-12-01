<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PriceRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Factory parameter is required')]
        #[Assert\Type('string')]
        public string $factory,

        #[Assert\NotBlank(message: 'Collection parameter is required')]
        #[Assert\Type('string')]
        public string $collection,

        #[Assert\NotBlank(message: 'Article parameter is required')]
        #[Assert\Type('string')]
        public string $article,
    ) {
    }
}
