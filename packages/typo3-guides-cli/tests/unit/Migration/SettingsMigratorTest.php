<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Migration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Migration\SettingsMigrator;

final class SettingsMigratorTest extends TestCase
{
    private SettingsMigrator $subject;

    protected function setUp(): void
    {
        $this->subject = new SettingsMigrator();
    }

    /**
     * @param array<string, array<string, string>> $legacySettings
     */
    #[Test]
    #[DataProvider('providerForMigrateReturnsXmlDocumentCorrectly')]
    public function migrateReturnsXmlDocumentCorrectly(array $legacySettings, string $expected): void
    {
        $actual = $this->subject->migrate($legacySettings)->xmlDocument->saveXML() ?: '';

        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    public static function providerForMigrateReturnsXmlDocumentCorrectly(): \Generator
    {
        yield 'with empty legacy settings' => [
            'legacy settings' => [],
            'expected' => <<<EXPECTED
                <?xml version="1.0" encoding="UTF-8"?>
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd" links-are-relative="true">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                </guides>
                EXPECTED,
        ];

        yield 'with all html_theme_options given' => [
            'legacy settings' => [
                'html_theme_options' => [
                    'project_home' => 'https://example.org/',
                    'project_contact' => 'https://example.org/contact',
                    'project_repository' => 'https://example.org/repository',
                    'project_issues' => 'https://example.org/issues',
                    'project_discussions' => 'https://example.org/discussions',
                    'use_opensearch' => 'false',
                    'github_revision_msg' => 'some github message',
                    'github_branch' => 'main',
                    'github_repository' => 'my-example-repository',
                    'github_sphinx_locale' => 'de',
                    'github_commit_hash' => 'abcdef',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension
                        class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
                        edit-on-github="my-example-repository"
                        edit-on-github-branch="main"
                        github-commit-hash="abcdef"
                        github-revision-msg="some github message"
                        github-sphinx-locale="de"
                        use-opensearch="false"
                        project-contact="https://example.org/contact"
                        project-discussions="https://example.org/discussions"
                        project-home="https://example.org/"
                        project-issues="https://example.org/issues"
                        project-repository="https://example.org/repository"
                        typo3-core-preferred="stable"
                    />
                </guides>
                EXPECTED,
        ];

        yield 'with only one of the html_theme_options given' => [
            'legacy settings' => [
                'html_theme_options' => [
                    'project_home' => 'https://example.org/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" project-home="https://example.org/" typo3-core-preferred="stable"/>
                </guides>
                EXPECTED,
        ];

        yield 'with all general options given' => [
            'legacy settings' => [
                'general' => [
                    'project' => 'Some project',
                    'version' => '1.0',
                    'release' => '1.0.3',
                    'copyright' => 'Some copyright',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <project
                        copyright="Some copyright"
                        release="1.0.3"
                        title="Some project"
                        version="1.0"
                    />
                </guides>
                EXPECTED,
        ];

        yield 'with only one of the general options given' => [
            'legacy settings' => [
                'general' => [
                    'project' => 'Some project',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <project
                        title="Some project"
                    />
                </guides>
                EXPECTED,
        ];

        yield 'with intersphinx_mapping given' => [
            'legacy settings' => [
                'intersphinx_mapping' => [
                    'manual_1' => 'https://example.com/manual-1/',
                    'manual_2' => 'https://example.com/manual-2/',
                    'manual_3' => 'https://example.com/manual-3/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <inventory id="manual_1" url="https://example.com/manual-1/"/>
                    <inventory id="manual_2" url="https://example.com/manual-2/"/>
                    <inventory id="manual_3" url="https://example.com/manual-3/"/>
                </guides>
                EXPECTED,
        ];

        yield 'with intersphinx_default_mapping given' => [
            'legacy settings' => [
                'intersphinx_mapping' => [
                    't3coreapi' => 'https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/',
                    't3viewhelper' => 'https://docs.typo3.org/other/typo3/view-helper-reference/12.4/en-us/',
                    'manual_3' => 'https://example.com/manual-3/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <inventory id="manual_3" url="https://example.com/manual-3/"/>
                </guides>
                EXPECTED,
        ];

        yield 'with intersphinx with default id but unknown url' => [
            'legacy settings' => [
                'intersphinx_mapping' => [
                    't3coreapi' => 'https://example.com/manual-3/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <inventory id="t3coreapi" url="https://example.com/manual-3/"/>
                </guides>
                EXPECTED,
        ];


        yield 'with intersphinx default id, conflicting versions given' => [
            'legacy settings' => [
                'intersphinx_mapping' => [
                    't3coreapi' => 'https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/',
                    't3viewhelper' => 'https://docs.typo3.org/other/typo3/view-helper-reference/11.5/en-us/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="stable"/>
                    <inventory id="t3viewhelper" url="https://docs.typo3.org/other/typo3/view-helper-reference/11.5/en-us/"/>
                </guides>
                EXPECTED,
        ];
        yield 'with intersphinx default id, non-stable preferred TYPO3 version' => [
            'legacy settings' => [
                'intersphinx_mapping' => [
                    't3viewhelper' => 'https://docs.typo3.org/other/typo3/view-helper-reference/11.5/en-us/',
                    't3coreapi' => 'https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" typo3-core-preferred="11.5"/>
                    <inventory id="t3coreapi" url="https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/"/>
                </guides>
                EXPECTED,
        ];

        yield 'with all sections given' => [
            'legacy settings' => [
                'html_theme_options' => [
                    'project_home' => 'https://example.org/',
                ],
                'general' => [
                    'project' => 'Some project',
                ],
                'intersphinx_mapping' => [
                    'manual_1' => 'https://example.com/manual-1/',
                ],
            ],
            'expected' => <<<EXPECTED
                <guides xmlns="https://www.phpdoc.org/guides" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" links-are-relative="true" xsi:schemaLocation="https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" project-home="https://example.org/"  typo3-core-preferred="stable"/>
                    <project title="Some project"/>
                    <inventory id="manual_1" url="https://example.com/manual-1/"/>
                </guides>
                EXPECTED,
        ];
    }

    #[Test]
    public function migrateReturnsTheNumberOfConvertedSettingsCorrectly(): void
    {
        $legacySettings = [
            'html_theme_options' => [
                'project_home' => 'https://example.org/',
                'project_repository' => 'https://example.org/repository',
                'project_issues' => 'https://example.org/issues',
            ],
            'general' => [
                'project' => 'Some project',
                'version' => '1.0',
                'unknown' => 'some value',
            ],
            'intersphinx_mapping' => [
                'manual_1' => 'https://example.com/manual-1/',
            ],
        ];

        $actual = $this->subject->migrate($legacySettings)->numberOfConvertedSettings;

        self::assertSame(6, $actual);
    }

    /**
     * @param array<string, array<string, string>> $legacySettings
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('providerForMigrateReturnsMessagesCorrectly')]
    public function migrateReturnsMessagesCorrectly(array $legacySettings, array $expected): void
    {
        $actual = $this->subject->migrate($legacySettings)->messages;

        self::assertSame($expected, $actual);
    }

    public static function providerForMigrateReturnsMessagesCorrectly(): \Generator
    {
        yield 'no messages given with empty legacy settings' => [
            'legacy settings' => [],
            'expected' => [],
        ];

        yield 'messages given with unknown legacy settings' => [
            'legacy settings' => [
                'html_theme_options' => [
                    'unknown_html_theme_option' => 'some value',
                ],
                'project' => [
                    'unknown_project_setting' => 'another value',
                ],
            ],
            'expected' => [
                'Note: Some of your settings could not be converted:',
                '  * html_theme_options',
                '    * unknown_html_theme_option',
                '  * project',
            ],
        ];
    }
}
