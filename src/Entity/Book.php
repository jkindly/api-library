<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotBorrowedException;
use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'book')]
#[ORM\UniqueConstraint(name: 'uniq_book_serial_number', columns: ['serial_number'])]
class Book
{
    #[ORM\Column(length: 6, unique: true)]
    public string $serialNumber {
        get {
            return $this->serialNumber;
        }
    }

    #[ORM\Column(length: 255)]
    public string $title {
        get {
            return $this->title;
        }
    }

    #[ORM\Column(length: 255)]
    public string $author {
        get {
            return $this->author;
        }
    }

    #[ORM\Column]
    public bool $borrowed = false {
        get {
            return $this->borrowed;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $borrowedAt = null {
        get {
            return $this->borrowedAt;
        }
    }

    #[ORM\Column(length: 6, nullable: true)]
    public ?string $borrowerCardNumber = null {
        get {
            return $this->borrowerCardNumber;
        }
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null {
        get {
            return $this->id;
        }
    }

    /**
     * Optimistic-locking version. Combined with the pessimistic lock taken when
     * changing a book's status, this guards against lost updates under concurrency.
     */
    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    public function __construct(string $serialNumber, string $title, string $author)
    {
        $this->serialNumber = $serialNumber;
        $this->title = $title;
        $this->author = $author;
    }

    public function borrow(string $cardNumber, \DateTimeImmutable $when): void
    {
        if ($this->borrowed) {
            throw new BookAlreadyBorrowedException($this->serialNumber);
        }

        $this->borrowed = true;
        $this->borrowerCardNumber = $cardNumber;
        $this->borrowedAt = $when;
    }

    public function returnToLibrary(): void
    {
        if (! $this->borrowed) {
            throw new BookNotBorrowedException($this->serialNumber);
        }

        $this->borrowed = false;
        $this->borrowerCardNumber = null;
        $this->borrowedAt = null;
    }
}
