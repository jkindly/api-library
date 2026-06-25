<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        // Each test starts from a clean schema on the dedicated test database.
        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    protected function jsonRequest(string $method, string $uri, ?array $payload = null): void
    {
        $this->client->request(
            $method,
            $uri,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getResponseJson(): array
    {
        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private function resetDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}
