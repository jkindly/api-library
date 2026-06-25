<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class BookNotBorrowedException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book "%s" is not currently borrowed.', $serialNumber));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
