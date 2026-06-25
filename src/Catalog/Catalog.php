<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Dto\CreateBookRequest;
use App\Dto\UpdateBookStatusRequest;
use App\Entity\Book;
use App\Enum\BookStatus;
use App\Exception\BookNotFoundException;
use App\Exception\DuplicateSerialNumberException;
use App\Repository\BookRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * The library's book catalog: the application-level operations for tracking and
 * updating the collection (transactions, locking and exception translation).
 */
final readonly class Catalog
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $bookRepository,
        private ClockInterface $clock,
    ) {
    }

    public function create(CreateBookRequest $request): Book
    {
        $book = new Book($request->serialNumber, $request->title, $request->author);

        try {
            $this->bookRepository->save($book);
        } catch (UniqueConstraintViolationException) {
            // Two employees may submit the same serial number concurrently; the
            // unique index is the source of truth for serial-number uniqueness.
            throw new DuplicateSerialNumberException($request->serialNumber);
        }

        return $book;
    }

    /**
     * @return Book[]
     */
    public function list(): array
    {
        return $this->bookRepository->findAllOrderedBySerialNumber();
    }

    public function delete(string $serialNumber): void
    {
        $book = $this->bookRepository->findOneBySerialNumber($serialNumber);

        if ($book === null) {
            throw new BookNotFoundException($serialNumber);
        }

        $this->bookRepository->remove($book);
    }

    public function changeStatus(string $serialNumber, UpdateBookStatusRequest $request): Book
    {
        return $this->entityManager->wrapInTransaction(function () use ($serialNumber, $request): Book {
            $book = $this->bookRepository->findOneBySerialNumberForUpdate($serialNumber);

            if ($book === null) {
                throw new BookNotFoundException($serialNumber);
            }

            match ($request->status) {
                BookStatus::Borrowed => $book->borrow((string) $request->cardNumber, $this->clock->now()),
                BookStatus::Available => $book->returnToLibrary(),
                null => throw new \LogicException('Status must be validated before reaching the service.'),
            };

            $this->entityManager->flush();

            return $book;
        });
    }
}
