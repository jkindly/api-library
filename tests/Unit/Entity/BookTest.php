<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Book;
use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotBorrowedException;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testNewBookIsAvailable(): void
    {
        $book = new Book('000123', 'Lem: Solaris', 'Stanisław Lem');

        self::assertFalse($book->borrowed);
        self::assertNull($book->borrowedAt);
        self::assertNull($book->borrowerCardNumber);
    }

    public function testBorrowMarksBookAsBorrowed(): void
    {
        $book = new Book('000123', 'Solaris', 'Stanisław Lem');
        $when = new \DateTimeImmutable('2026-06-23 10:00:00');

        $book->borrow('654321', $when);

        self::assertTrue($book->borrowed);
        self::assertSame('654321', $book->borrowerCardNumber);
        self::assertSame($when, $book->borrowedAt);
    }

    public function testBorrowingAnAlreadyBorrowedBookThrows(): void
    {
        $book = new Book('000123', 'Solaris', 'Stanisław Lem');
        $book->borrow('654321', new \DateTimeImmutable());

        $this->expectException(BookAlreadyBorrowedException::class);

        $book->borrow('111111', new \DateTimeImmutable());
    }

    public function testReturnResetsBorrowState(): void
    {
        $book = new Book('000123', 'Solaris', 'Stanisław Lem');
        $book->borrow('654321', new \DateTimeImmutable());

        $book->returnToLibrary();

        self::assertFalse($book->borrowed);
        self::assertNull($book->borrowedAt);
        self::assertNull($book->borrowerCardNumber);
    }

    public function testReturningAnAvailableBookThrows(): void
    {
        $book = new Book('000123', 'Solaris', 'Stanisław Lem');

        $this->expectException(BookNotBorrowedException::class);

        $book->returnToLibrary();
    }
}
