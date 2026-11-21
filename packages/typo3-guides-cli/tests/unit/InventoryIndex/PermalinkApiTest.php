<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\InventoryIndex;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\InventoryIndex\Artifact;
use T3Docs\GuidesCli\InventoryIndex\InventoryDatabase;
use T3Docs\GuidesCli\InventoryIndex\Permalink;
use T3Docs\GuidesCli\InventoryIndex\PermalinkApi;

use function array_column;
use function array_unique;
use function array_values;
use function count;

final class PermalinkApiTest extends TestCase
{
    private PermalinkApi $api;

    protected function setUp(): void
    {
        $connection = new PDO('sqlite::memory:');
        $database = new InventoryDatabase($connection);
        $this->api = new PermalinkApi($connection);

        $database->replaceInventory(
            new Artifact('reference-tca', 'https://docs.typo3.org/m/typo3/reference-tca/main/en-us', 'main', 'en-us'),
            [
                new Permalink('std:label', 'columns', 'Columns/Index.html#columns'),
                new Permalink('std:label', 'start', 'Index.html#start'),
            ],
        );
        $database->replaceInventory(
            new Artifact('reference-tca', 'https://docs.typo3.org/m/typo3/reference-tca/12.4/en-us', '12.4', 'en-us'),
            [new Permalink('std:label', 'columns', 'Columns/Index.html#columns')],
        );
        $database->replaceInventory(
            new Artifact('reference-coreapi', 'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us', 'main', 'en-us'),
            [new Permalink('std:label', 'start', 'Index.html#start')],
        );
        $database->replaceInventory(
            new Artifact('t3docs/render-guides', 'https://docs.typo3.org/other/t3docs/render-guides/main/de-de', 'main', 'de-de'),
            [new Permalink('std:doc', 'Index', 'Index.html')],
        );
    }

    #[Test]
    public function listsTheVersionsOfARepositoryInOrder(): void
    {
        $response = $this->api->handle('GET', '/versions', ['repository' => 'reference-tca']);

        self::assertSame(200, $response['status']);
        self::assertSame(['12.4', 'main'], $response['body']['versions']);
    }

    #[Test]
    public function listsTheLanguagesOfARepository(): void
    {
        $response = $this->api->handle('GET', '/languages', ['repository' => 'reference-tca']);

        self::assertSame(200, $response['status']);
        self::assertSame(['en-us'], $response['body']['languages']);
    }

    #[Test]
    public function listsThePermalinksOfOneVersion(): void
    {
        $response = $this->api->handle('GET', '/permalinks', [
            'repository' => 'reference-tca',
            'version' => 'main',
        ]);

        self::assertSame(200, $response['status']);
        self::assertSame('main', $response['body']['version']);
        self::assertSame(2, $response['body']['count']);
        self::assertSame(
            ['main'],
            array_values(array_unique(array_column($response['body']['permalinks'], 'version'))),
        );
    }

    #[Test]
    public function listsThePermalinksOfAllVersions(): void
    {
        $response = $this->api->handle('GET', '/permalinks', ['repository' => 'reference-tca']);

        self::assertSame(200, $response['status']);
        self::assertSame(3, $response['body']['count']);
        self::assertSame(
            ['12.4', 'main'],
            array_values(array_unique(array_column($response['body']['permalinks'], 'version'))),
        );
    }

    #[Test]
    public function listsTheManualsBelowADirectory(): void
    {
        $response = $this->api->handle('GET', '/manuals', ['directory' => 'm']);

        self::assertSame(200, $response['status']);
        self::assertSame(['reference-coreapi', 'reference-tca'], $response['body']['manuals']);
    }

    #[Test]
    public function keepsARepositoryWithASlashInItsNameIntact(): void
    {
        $response = $this->api->handle('GET', '/manuals', ['directory' => 'other']);

        self::assertSame(200, $response['status']);
        self::assertSame(1, count($response['body']['manuals']));
        self::assertSame(['t3docs/render-guides'], $response['body']['manuals']);
    }

    #[Test]
    public function rejectsAnUnknownRepository(): void
    {
        $response = $this->api->handle('GET', '/versions', ['repository' => 'unknown']);

        self::assertSame(404, $response['status']);
        self::assertArrayHasKey('error', $response['body']);
    }

    #[Test]
    public function rejectsAnUnknownVersion(): void
    {
        $response = $this->api->handle('GET', '/permalinks', [
            'repository' => 'reference-tca',
            'version' => '11.5',
        ]);

        self::assertSame(404, $response['status']);
    }

    #[Test]
    public function requiresTheRepositoryParameter(): void
    {
        $response = $this->api->handle('GET', '/versions');

        self::assertSame(400, $response['status']);
        self::assertArrayHasKey('error', $response['body']);
    }

    #[Test]
    public function rejectsAnUnknownEndpoint(): void
    {
        $response = $this->api->handle('GET', '/nope');

        self::assertSame(404, $response['status']);
        self::assertArrayHasKey('endpoints', $response['body']);
    }

    #[Test]
    public function rejectsMethodsOtherThanGet(): void
    {
        $response = $this->api->handle('POST', '/versions', ['repository' => 'reference-tca']);

        self::assertSame(405, $response['status']);
    }
}
