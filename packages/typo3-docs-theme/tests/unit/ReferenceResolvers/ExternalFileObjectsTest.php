<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\ReferenceResolvers;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInterlinkParser;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInventoryUrlBuilder;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ExternalFileObjects;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * The integration fixtures never fetch: they hand the definitions over, so
 * where they come from and what happens when they cannot be had is covered here.
 */
#[CoversClass(ExternalFileObjects::class)]
final class ExternalFileObjectsTest extends TestCase
{
    private JsonLoader&MockObject $jsonLoader;

    protected function setUp(): void
    {
        $this->jsonLoader = $this->createMock(JsonLoader::class);
    }

    public function testReadsTheFilesOfTheTypo3ExplainedTheManualLinksTo(): void
    {
        $this->jsonLoader->expects(self::once())
            ->method('loadJsonFromUrl')
            ->with('https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/files.json')
            ->willReturn(['files' => [
                [
                    'id' => 'extension-ext-tables-sql',
                    'fileName' => 'ext_tables.sql',
                    'regex' => '/^.*ext_tables\.sql$/',
                    'path' => 'ExtensionArchitecture/FileStructure/ExtTablesSql.html#file-extension-ext-tables-sql',
                ],
                // Nothing to link to
                ['id' => 'no-path', 'fileName' => 'a.txt'],
                // Not a file at all
                ['id' => 'no-name', 'path' => 'Index.html#file-no-name'],
                ['id' => ['not', 'a', 'string'], 'fileName' => 'b.txt', 'path' => 'Index.html#file-b'],
                'not an entry',
            ]]);

        $subject = $this->subject(['typo3_core_preferred' => '12.4']);
        $fileObjects = $subject->all();
        // Fetched once for the whole render
        $subject->all();

        self::assertCount(1, $fileObjects);
        self::assertSame('ext_tables.sql', $fileObjects[0]->fileName);
        self::assertSame(
            'https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/FileStructure/ExtTablesSql.html#file-extension-ext-tables-sql',
            $fileObjects[0]->url,
        );
        self::assertTrue($fileObjects[0]->matchesRegex('EXT:my_extension/ext_tables.sql'));
    }

    public function testTypo3ExplainedDoesNotLookUpItself(): void
    {
        $this->jsonLoader->expects(self::never())->method('loadJsonFromUrl');

        self::assertSame([], $this->subject(['interlink_shortcode' => 't3coreapi'])->all());
    }

    public function testAManualRendersWhenTheFileCannotBeFetched(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willThrowException(new RuntimeException('404'));

        self::assertSame([], $this->subject([])->all());
    }

    public function testAnInvalidRegexMatchesNothing(): void
    {
        $subject = $this->subject([]);
        $subject->useDefinitions(
            ['files' => [['id' => 'broken', 'fileName' => 'x', 'regex' => '/(/', 'path' => 'Index.html#file-broken']]],
            'https://example.org/',
        );

        self::assertFalse($subject->all()[0]->matchesRegex('x'));
    }

    /** @param array<string, string> $settings */
    private function subject(array $settings): ExternalFileObjects
    {
        $anchorNormalizer = new SluggerAnchorNormalizer();
        $themeSettings = new Typo3DocsThemeSettings($settings);
        $versionService = new Typo3VersionService($themeSettings);

        return new ExternalFileObjects(
            new Typo3InventoryRepository(
                new NullLogger(),
                $anchorNormalizer,
                new DefaultInventoryLoader(new NullLogger(), $this->jsonLoader, $anchorNormalizer),
                $this->jsonLoader,
                $versionService,
                [],
                new DefaultInterlinkParser($anchorNormalizer),
                new DefaultInventoryUrlBuilder($versionService),
            ),
            $this->jsonLoader,
            $themeSettings,
            new NullLogger(),
        );
    }
}
