<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\TypoScriptReference;

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
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\Typo3DocsTheme\TypoScriptReference\ExternalTypoScript;
use T3Docs\Typo3DocsTheme\TypoScriptReference\ObjectTypes;
use T3Docs\Typo3DocsTheme\TypoScriptReference\OptionPaths;
use T3Docs\Typo3DocsTheme\TypoScriptReference\Options;

/**
 * The integration fixtures never fetch: they hand the reference's files over,
 * so where they come from and what happens without them is covered here.
 */
#[CoversClass(ExternalTypoScript::class)]
#[CoversClass(ObjectTypes::class)]
#[CoversClass(OptionPaths::class)]
#[CoversClass(Options::class)]
final class ExternalTypoScriptTest extends TestCase
{
    private const BASE_URL = 'https://docs.typo3.org/m/typo3/reference-typoscript/13.4/en-us/';

    private JsonLoader&MockObject $jsonLoader;

    protected function setUp(): void
    {
        $this->jsonLoader = $this->createMock(JsonLoader::class);
    }

    public function testReadsTheOptionsOfTheReferenceTheManualLinksTo(): void
    {
        $this->jsonLoader->expects(self::once())
            ->method('loadJsonFromUrl')
            ->with(self::BASE_URL . 'confvals.json')
            ->willReturn(['confvals' => [
                'confval-stdwrap-parsefunc' => ['name' => 'parseFunc', 'searchFacet' => 'TypoScript', 'type' => 'parseFunc', 'summary' => 'Parses content.', 'path' => 'Functions/Stdwrap'],
                // No TypoScript, although a path could name it
                'confval-columns-label' => ['name' => 'label', 'searchFacet' => 'TCA', 'path' => 'Columns/Index'],
                // Nothing to link to
                'confval-broken' => ['name' => 'broken', 'searchFacet' => 'TypoScript'],
                'not an entry',
            ]]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        self::assertSame(
            ['name' => 'parseFunc', 'type' => 'parseFunc', 'summary' => 'Parses content.', 'url' => self::BASE_URL . 'Functions/Stdwrap.html#confval-stdwrap-parsefunc'],
            $subject->findOption('stdWrap.parseFunc'),
        );
        self::assertNull($subject->findOption('columns.label'));
        // Fetched once for the whole render
        self::assertNull($subject->findOption('broken.option'));
    }

    public function testFindsAnOptionByThePathItDeclares(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willReturn(['confvals' => [
            'confval-useroptions-pagetree-showpageidwithtitle' => ['name' => 'showPageIdWithTitle', 'searchFacet' => 'TypoScript', 'path' => 'UserTsconfig/Options', 'fields' => ['User TSconfig path' => 'options.pageTree.showPageIdWithTitle']],
            // Two options declaring one path tell nothing
            'confval-a' => ['name' => 'a', 'searchFacet' => 'TypoScript', 'path' => 'A', 'fields' => ['Page TSconfig path' => 'mod.twice']],
            'confval-b' => ['name' => 'b', 'searchFacet' => 'TypoScript', 'path' => 'B', 'fields' => ['Page TSconfig path' => 'mod.twice']],
            // A placeholder is no path a role writes
            'confval-c' => ['name' => 'c', 'searchFacet' => 'TypoScript', 'path' => 'C', 'fields' => ['Page TSconfig path' => 'mod.[group].c']],
        ]]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        self::assertSame(
            self::BASE_URL . 'UserTsconfig/Options.html#confval-useroptions-pagetree-showpageidwithtitle',
            $subject->findOption('options.pageTree.showPageIdWithTitle')['url'] ?? null,
        );
        self::assertNull($subject->findOption('mod.twice'));
        self::assertNull($subject->findOption('mod.group.c'));
    }

    public function testFindsAPluginOptionBelowTheKeyOfAPlugin(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willReturn(['confvals' => [
            'confval-plugin-persistence-storagepid' => ['name' => 'persistence.storagePid', 'searchFacet' => 'TypoScript', 'path' => 'TopLevelObjects/Plugin'],
        ]]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        $url = self::BASE_URL . 'TopLevelObjects/Plugin.html#confval-plugin-persistence-storagepid';
        self::assertSame($url, $subject->findOption('plugin.persistence.storagePid')['url'] ?? null);
        self::assertSame($url, $subject->findOption('plugin.tx_blog.persistence.storagePid')['url'] ?? null);
        self::assertSame($url, $subject->findOption('plugin.tx_blog_list.persistence.storagePid')['url'] ?? null);
        // Only the key of a plugin is left out
        self::assertNull($subject->findOption('plugin.blog.persistence.storagePid'));
        self::assertNull($subject->findOption('module.tx_blog.persistence.storagePid'));
        // Without the plugin, the path is ambiguous
        self::assertNull($subject->findOption('persistence.storagePid'));
    }

    public function testFindsAnObjectTypeByItsAnchorOrItsPage(): void
    {
        $this->jsonLoader->expects(self::once())
            ->method('loadJsonFromUrl')
            ->with(self::BASE_URL . 'objects.inv.json')
            ->willReturn([
                'std:label' => [
                    'cobj-coa-int' => ['TypoScript', '13.4', 'ContentObjects/CoaAndCoaInt/Index.html#cobj-coa-int', 'Content object array - COA, COA_INT'],
                    // An anchor whose headline is about something else
                    'cobj-case' => ['TypoScript', '13.4', 'Other.html#cobj-case', 'In case of doubt'],
                ],
                'std:doc' => [
                    'Gifbuilder/Emboss/Index' => ['TypoScript', '13.4', 'Gifbuilder/Emboss/Index.html', 'EMBOSS'],
                    // Two pages of the same title tell nothing
                    'A/Index' => ['TypoScript', '13.4', 'A/Index.html', 'TWICE'],
                    'B/Index' => ['TypoScript', '13.4', 'B/Index.html', 'TWICE'],
                ],
            ]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        self::assertSame(
            ['title' => 'Content object array - COA, COA_INT', 'url' => self::BASE_URL . 'ContentObjects/CoaAndCoaInt/Index.html#cobj-coa-int'],
            $subject->findObjectType('COA_INT'),
        );
        self::assertSame(['title' => 'EMBOSS', 'url' => self::BASE_URL . 'Gifbuilder/Emboss/Index.html'], $subject->findObjectType('EMBOSS'));
        self::assertNull($subject->findObjectType('CASE'));
        self::assertNull($subject->findObjectType('TWICE'));
    }

    public function testFindsAnyOptionOfAnotherManualByItsName(): void
    {
        $this->jsonLoader->expects(self::once())
            ->method('loadJsonFromUrl')
            ->with('https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/confvals.json')
            ->willReturn(['confvals' => [
                // Not TypoScript, so a path would not find it; a name does
                'confval-extbase-config' => ['name' => 'config.tx_extbase', 'searchFacet' => 'Option', 'summary' => 'Extbase.', 'path' => 'Extbase/Reference'],
            ]]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        [$loaded, $option] = $subject->findNamed('t3coreapi', 'extbase-config');
        self::assertTrue($loaded);
        self::assertSame('https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Extbase/Reference.html#confval-extbase-config', $option['url'] ?? null);
        // Read once for the whole render, and unknown names stay unknown
        self::assertSame([true, null], $subject->findNamed('t3coreapi', 'no-such-option'));
    }

    public function testAManualThatCannotBeReadIsToldApartFromAnUnknownName(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willThrowException(new RuntimeException('Connection refused'));

        self::assertSame([false, null], $this->subject([])->findNamed('t3coreapi', 'extbase-config'));
    }

    public function testTheReferenceDoesNotLookUpItself(): void
    {
        $this->jsonLoader->expects(self::never())->method('loadJsonFromUrl');

        $subject = $this->subject(['interlink_shortcode' => 't3tsref']);

        self::assertNull($subject->findOption('stdWrap.parseFunc'));
        self::assertNull($subject->findObjectType('PAGEVIEW'));
    }

    public function testAManualRendersWhenTheFilesCannotBeFetched(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willThrowException(new RuntimeException('404'));

        $subject = $this->subject([]);

        self::assertNull($subject->findOption('stdWrap.parseFunc'));
        self::assertNull($subject->findObjectType('PAGEVIEW'));
    }

    /** @param array<string, string> $settings */
    private function subject(array $settings): ExternalTypoScript
    {
        $anchorNormalizer = new SluggerAnchorNormalizer();
        $themeSettings = new Typo3DocsThemeSettings($settings);
        $versionService = new Typo3VersionService($themeSettings);

        return new ExternalTypoScript(
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
            $anchorNormalizer,
            new NullLogger(),
        );
    }
}
