<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\ApiExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders every exception thrown under /api as a consistent JSON payload.
 */
#[AsEventListener(event: 'kernel.exception')]
final readonly class JsonExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (! str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();

        $validationFailure = $this->findValidationFailure($throwable);
        if ($validationFailure !== null) {
            $event->setResponse(new JsonResponse([
                'error' => 'Validation failed.',
                'violations' => $this->formatViolations($validationFailure),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($throwable instanceof ApiExceptionInterface) {
            $event->setResponse(new JsonResponse(
                [
                    'error' => $throwable->getMessage(),
                ],
                $throwable->getStatusCode(),
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                [
                    'error' => $throwable->getMessage() ?: 'HTTP error.',
                ],
                $throwable->getStatusCode(),
                $throwable->getHeaders(),
            ));

            return;
        }

        $payload = [
            'error' => 'Internal server error.',
        ];
        if ($this->debug) {
            $payload['exception'] = $throwable::class;
            $payload['message'] = $throwable->getMessage();
        }

        $event->setResponse(new JsonResponse($payload, Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    private function findValidationFailure(\Throwable $throwable): ?ValidationFailedException
    {
        $current = $throwable;
        while ($current !== null) {
            if ($current instanceof ValidationFailedException) {
                return $current;
            }
            $current = $current->getPrevious();
        }

        return null;
    }

    /**
     * @return array<int, array{field: string, message: string}>
     */
    private function formatViolations(ValidationFailedException $exception): array
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
