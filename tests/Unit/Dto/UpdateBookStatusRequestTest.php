<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\UpdateBookStatusRequest;
use App\Enum\BookStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UpdateBookStatusRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testBorrowingWithCardNumberIsValid(): void
    {
        $request = new UpdateBookStatusRequest(BookStatus::Borrowed, '654321');

        self::assertCount(0, $this->validator->validate($request));
    }

    public function testReturningRequiresNoCardNumber(): void
    {
        $request = new UpdateBookStatusRequest(BookStatus::Available);

        self::assertCount(0, $this->validator->validate($request));
    }

    public function testBorrowingWithoutCardNumberIsRejected(): void
    {
        $request = new UpdateBookStatusRequest(BookStatus::Borrowed);

        $violations = $this->validator->validate($request);

        self::assertCount(1, $violations);
        self::assertSame('cardNumber', $violations->get(0)->getPropertyPath());
    }

    public function testMissingStatusIsRejected(): void
    {
        $request = new UpdateBookStatusRequest(null);

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('status', $violations->get(0)->getPropertyPath());
    }

    public function testMalformedCardNumberIsRejected(): void
    {
        $request = new UpdateBookStatusRequest(BookStatus::Borrowed, 'ABC');

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('cardNumber', $violations->get(0)->getPropertyPath());
    }
}
