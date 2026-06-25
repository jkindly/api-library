<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\CreateBookRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateBookRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidRequestHasNoViolations(): void
    {
        $request = new CreateBookRequest('000123', 'Solaris', 'Stanisław Lem');

        self::assertCount(0, $this->validator->validate($request));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSerialNumbers(): iterable
    {
        yield 'too short' => ['12'];
        yield 'too long' => ['1234567'];
        yield 'non-digits' => ['abc123'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidSerialNumbers')]
    public function testInvalidSerialNumberIsRejected(string $serialNumber): void
    {
        $request = new CreateBookRequest($serialNumber, 'Solaris', 'Lem');

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('serialNumber', $violations->get(0)->getPropertyPath());
    }

    public function testBlankTitleIsRejected(): void
    {
        $request = new CreateBookRequest('000123', '', 'Lem');

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('title', $violations->get(0)->getPropertyPath());
    }
}
