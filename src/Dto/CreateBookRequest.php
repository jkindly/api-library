<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateBookRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Serial number is required.')]
        #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Serial number must be exactly 6 digits.')]
        public string $serialNumber = '',
        #[Assert\NotBlank(message: 'Title is required.')]
        #[Assert\Length(max: 255)]
        public string $title = '',
        #[Assert\NotBlank(message: 'Author is required.')]
        #[Assert\Length(max: 255)]
        public string $author = '',
    ) {
    }
}
