<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class BookNotFoundException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book with serial number "%s" was not found.', $serialNumber));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
