<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\BookStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class UpdateBookStatusRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Status is required and must be "borrowed" or "available".')]
        public ?BookStatus $status = null,
        #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Card number must be exactly 6 digits.')]
        public ?string $cardNumber = null,
    ) {
    }

    #[Assert\Callback]
    public function validateCardNumber(ExecutionContextInterface $context): void
    {
        if ($this->status === BookStatus::Borrowed && ($this->cardNumber === null || $this->cardNumber === '')) {
            $context->buildViolation('Card number is required when borrowing a book.')
                ->atPath('cardNumber')
                ->addViolation();
        }
    }
}
