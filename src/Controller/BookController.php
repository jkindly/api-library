<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\Catalog;
use App\Dto\BookResponse;
use App\Dto\CreateBookRequest;
use App\Dto\UpdateBookStatusRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
final readonly class BookController
{
    public function __construct(
        private Catalog $catalog
    ) {
    }

    #[Route('', name: 'book_list', methods: [Request::METHOD_GET])]
    public function list(): JsonResponse
    {
        $books = array_map(
            BookResponse::fromEntity(...),
            $this->catalog->list(),
        );

        return new JsonResponse($books);
    }

    #[Route('', name: 'book_create', methods: [Request::METHOD_POST])]
    public function create(#[MapRequestPayload] CreateBookRequest $request): JsonResponse
    {
        $book = $this->catalog->create($request);

        return new JsonResponse(BookResponse::fromEntity($book), Response::HTTP_CREATED);
    }

    #[Route('/{serialNumber}', name: 'book_delete', requirements: [
        'serialNumber' => '\d{6}',
    ], methods: [Request::METHOD_DELETE])]
    public function delete(string $serialNumber): JsonResponse
    {
        $this->catalog->delete($serialNumber);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{serialNumber}/status', name: 'book_update_status', requirements: [
        'serialNumber' => '\d{6}',
    ], methods: [Request::METHOD_PATCH])]
    public function updateStatus(
        string $serialNumber,
        #[MapRequestPayload]
        UpdateBookStatusRequest $request,
    ): JsonResponse {
        $book = $this->catalog->changeStatus($serialNumber, $request);

        return new JsonResponse(BookResponse::fromEntity($book));
    }
}
