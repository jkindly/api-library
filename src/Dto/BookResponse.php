<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Book;

final readonly class BookResponse implements \JsonSerializable
{
    public function __construct(
        public string $serialNumber,
        public string $title,
        public string $author,
        public bool $borrowed,
        public ?string $borrowedAt,
        public ?string $borrowerCardNumber,
    ) {
    }

    public static function fromEntity(Book $book): self
    {
        return new self(
            $book->serialNumber,
            $book->title,
            $book->author,
            $book->borrowed,
            $book->borrowedAt?->format(\DateTimeInterface::ATOM),
            $book->borrowerCardNumber,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'serialNumber' => $this->serialNumber,
            'title' => $this->title,
            'author' => $this->author,
            'borrowed' => $this->borrowed,
            'borrowedAt' => $this->borrowedAt,
            'borrowerCardNumber' => $this->borrowerCardNumber,
        ];
    }
}
