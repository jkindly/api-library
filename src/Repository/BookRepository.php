<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @throws UniqueConstraintViolationException if the serial number is already taken
     */
    public function save(Book $book): void
    {
        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();
    }

    public function remove(Book $book): void
    {
        $this->getEntityManager()->remove($book);
        $this->getEntityManager()->flush();
    }

    public function findOneBySerialNumber(string $serialNumber): ?Book
    {
        return $this->findOneBy([
            'serialNumber' => $serialNumber,
        ]);
    }

    public function findOneBySerialNumberForUpdate(string $serialNumber): ?Book
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.serialNumber = :serialNumber')
            ->setParameter('serialNumber', $serialNumber)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return Book[]
     */
    public function findAllOrderedBySerialNumber(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.serialNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
