<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

final class BookApiTest extends ApiWebTestCase
{
    public function testCreateThenListBook(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'serialNumber' => '000123',
            'title' => 'Solaris',
            'author' => 'Stanisław Lem',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->getResponseJson();
        self::assertSame('000123', $created['serialNumber']);
        self::assertFalse($created['borrowed']);
        self::assertNull($created['borrowedAt']);
        self::assertNull($created['borrowerCardNumber']);

        $this->jsonRequest('GET', '/api/books');

        self::assertResponseIsSuccessful();
        $list = $this->getResponseJson();
        self::assertCount(1, $list);
        self::assertSame('Solaris', $list[0]['title']);
    }

    public function testCreateDuplicateSerialNumberReturnsConflict(): void
    {
        $payload = [
            'serialNumber' => '000123',
            'title' => 'Solaris',
            'author' => 'Lem',
        ];

        $this->jsonRequest('POST', '/api/books', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->jsonRequest('POST', '/api/books', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testCreateWithInvalidPayloadReturnsValidationError(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'serialNumber' => '12',
            'title' => '',
            'author' => 'Lem',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $body = $this->getResponseJson();
        self::assertSame('Validation failed.', $body['error']);
        $fields = array_column($body['violations'], 'field');
        self::assertContains('serialNumber', $fields);
        self::assertContains('title', $fields);
    }

    public function testBorrowThenReturnFlow(): void
    {
        $this->createBook('000123');

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'borrowed',
            'cardNumber' => '654321',
        ]);
        self::assertResponseIsSuccessful();
        $borrowed = $this->getResponseJson();
        self::assertTrue($borrowed['borrowed']);
        self::assertSame('654321', $borrowed['borrowerCardNumber']);
        self::assertNotNull($borrowed['borrowedAt']);

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'available',
        ]);
        self::assertResponseIsSuccessful();
        $returned = $this->getResponseJson();
        self::assertFalse($returned['borrowed']);
        self::assertNull($returned['borrowerCardNumber']);
        self::assertNull($returned['borrowedAt']);
    }

    public function testBorrowingAnAlreadyBorrowedBookReturnsConflict(): void
    {
        $this->createBook('000123');

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'borrowed',
            'cardNumber' => '654321',
        ]);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'borrowed',
            'cardNumber' => '111111',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testReturningAnAvailableBookReturnsConflict(): void
    {
        $this->createBook('000123');

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'available',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testBorrowingWithoutCardNumberReturnsValidationError(): void
    {
        $this->createBook('000123');

        $this->jsonRequest('PATCH', '/api/books/000123/status', [
            'status' => 'borrowed',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $fields = array_column($this->getResponseJson()['violations'], 'field');
        self::assertContains('cardNumber', $fields);
    }

    public function testChangingStatusOfMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('PATCH', '/api/books/999999/status', [
            'status' => 'available',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteBook(): void
    {
        $this->createBook('000123');

        $this->client->request('DELETE', '/api/books/000123');
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->jsonRequest('GET', '/api/books');
        self::assertCount(0, $this->getResponseJson());
    }

    public function testDeleteMissingBookReturnsNotFound(): void
    {
        $this->client->request('DELETE', '/api/books/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createBook(string $serialNumber): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'serialNumber' => $serialNumber,
            'title' => 'Solaris',
            'author' => 'Stanisław Lem',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
