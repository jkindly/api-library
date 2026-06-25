<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the book table with a unique serial number constraint.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE book (id SERIAL NOT NULL, serial_number VARCHAR(6) NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, borrowed BOOLEAN NOT NULL DEFAULT FALSE, borrowed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, borrower_card_number VARCHAR(6) DEFAULT NULL, version INT NOT NULL DEFAULT 1, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_book_serial_number ON book (serial_number)');
        $this->addSql("COMMENT ON COLUMN book.borrowed_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE book');
    }
}
