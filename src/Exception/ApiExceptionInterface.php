<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Domain exception that knows which HTTP status code it should map to.
 */
interface ApiExceptionInterface extends \Throwable
{
    public function getStatusCode(): int;
}
