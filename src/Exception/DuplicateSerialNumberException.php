<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class DuplicateSerialNumberException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('A book with serial number "%s" already exists.', $serialNumber));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
