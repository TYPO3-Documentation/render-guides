<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\InventoryIndex;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\InventoryIndex\Artifact;
use T3Docs\GuidesCli\InventoryIndex\InvalidInventoryException;
use T3Docs\GuidesCli\InventoryIndex\InventoryDatabase;
use T3Docs\GuidesCli\InventoryIndex\InventoryIndexer;

use function file_put_contents;
use function json_encode;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class InventoryIndexerTest extends TestCase
{
    private PDO $connection;

    private InventoryIndexer $indexer;

    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:');
        $this->indexer = new InventoryIndexer(new InventoryDatabase($this->connection));
        $this->artifact = new Artifact(
            't3docs/render-guides',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us',
            'main',
            'en-us',
        );
    }

    #[Test]
    public function writesTheRepositoryAndEveryRoleOfTheInventory(): void
    {
        $count = $this->indexer->index($this->artifact, (string) json_encode([
            'std:doc' => [
                'Index' => ['Render Guides', 'main', 'Index.html', 'Render Guides'],
            ],
            'std:label' => [
                'start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides'],
                'columns' => ['TCA', 'main', 'Columns/Index.html#columns', 'Columns'],
            ],
            'std:confval-menu' => [
                'columns' => ['TCA', 'main', 'Columns/Index.html#confval-menu-columns', ''],
            ],
        ]));

        self::assertSame(4, $count);
        self::assertSame([[
            'repository' => 't3docs/render-guides',
            'url' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/',
            'version' => 'main',
            'language' => 'en-us',
        ]], $this->connection->query(
            'SELECT repository, url, version, language FROM repositories',
        )->fetchAll(PDO::FETCH_ASSOC));

        // The same anchor in two roles is two permalinks, not one overwriting
        // the other.
        self::assertSame([
            ['role' => 'std:confval-menu', 'anchor' => 'columns', 'target' => 'Columns/Index.html#confval-menu-columns'],
            ['role' => 'std:doc', 'anchor' => 'Index', 'target' => 'Index.html'],
            ['role' => 'std:label', 'anchor' => 'columns', 'target' => 'Columns/Index.html#columns'],
            ['role' => 'std:label', 'anchor' => 'start', 'target' => 'Index.html#start'],
        ], $this->connection->query(
            'SELECT role, anchor, target FROM permalinks ORDER BY role, anchor',
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    #[Test]
    public function indexingTheSameArtifactAgainReplacesItsPermalinksAndKeepsItsUid(): void
    {
        $this->indexer->index($this->artifact, (string) json_encode([
            'std:label' => [
                'start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides'],
                'removed' => ['Render Guides', 'main', 'Old.html#removed', 'Removed'],
            ],
        ]));
        $uid = $this->connection->query('SELECT uid FROM repositories')->fetchColumn();

        $moved = new Artifact(
            't3docs/render-guides',
            'https://preview.example.org/other/t3docs/render-guides/main/en-us/',
            'main',
            'en-us',
        );
        $count = $this->indexer->index($moved, (string) json_encode([
            'std:label' => [
                'start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides'],
                'added' => ['Render Guides', 'main', 'New.html#added', 'Added'],
            ],
        ]));

        self::assertSame(2, $count);
        self::assertSame(1, (int) $this->connection->query('SELECT COUNT(*) FROM repositories')->fetchColumn());
        self::assertSame((int) $uid, (int) $this->connection->query('SELECT uid FROM repositories')->fetchColumn());
        self::assertSame(
            'https://preview.example.org/other/t3docs/render-guides/main/en-us/',
            $this->connection->query('SELECT url FROM repositories')->fetchColumn(),
        );
        self::assertSame(
            ['added', 'start'],
            $this->connection->query('SELECT anchor FROM permalinks ORDER BY anchor')->fetchAll(PDO::FETCH_COLUMN),
        );
    }

    #[Test]
    public function anEmptyInventoryStillRecordsTheRepositoryAndDropsOldPermalinks(): void
    {
        $this->indexer->index($this->artifact, (string) json_encode([
            'std:label' => ['start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides']],
        ]));

        self::assertSame(0, $this->indexer->index($this->artifact, '{}'));
        self::assertSame(1, (int) $this->connection->query('SELECT COUNT(*) FROM repositories')->fetchColumn());
        self::assertSame(0, (int) $this->connection->query('SELECT COUNT(*) FROM permalinks')->fetchColumn());
    }

    #[Test]
    public function aDifferentLanguageIsADifferentRepositoryRow(): void
    {
        $this->indexer->index($this->artifact, '{}');
        $this->indexer->index(
            new Artifact('t3docs/render-guides', $this->artifact->url, 'main', 'de-de'),
            '{}',
        );

        self::assertSame(
            ['de-de', 'en-us'],
            $this->connection->query('SELECT language FROM repositories ORDER BY language')->fetchAll(PDO::FETCH_COLUMN),
        );
    }

    #[Test]
    public function rejectsJsonThatIsNotAnObject(): void
    {
        $this->expectException(InvalidInventoryException::class);
        $this->expectExceptionMessage('must be a JSON object');

        $this->indexer->index($this->artifact, '"not an object"');
    }

    #[Test]
    public function rejectsMalformedEntries(): void
    {
        $this->expectException(InvalidInventoryException::class);
        $this->expectExceptionMessage('is not a [project, version, target, title] list');

        $this->indexer->index($this->artifact, '{"std:label": {"start": ["Render Guides", "main"]}}');
    }

    #[Test]
    public function rejectsInvalidJson(): void
    {
        $this->expectException(InvalidInventoryException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $this->indexer->index($this->artifact, '{"std:label":');
    }

    #[Test]
    public function readsAnInventoryFile(): void
    {
        $path = sys_get_temp_dir() . '/objects.inv.json-' . uniqid();
        file_put_contents($path, (string) json_encode([
            'std:label' => ['start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides']],
        ]));

        try {
            self::assertSame(1, $this->indexer->indexFile($this->artifact, $path));
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function reportsAMissingInventoryFile(): void
    {
        $this->expectException(InvalidInventoryException::class);
        $this->expectExceptionMessage('could not be read');

        $this->indexer->indexFile($this->artifact, sys_get_temp_dir() . '/does-not-exist-' . uniqid());
    }
}
