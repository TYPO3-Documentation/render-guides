<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Settings\ProjectSettings;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\AbstractLogger;
use Stringable;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CheckEntryFileNameNodeTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\SourceFormat;

#[CoversClass(CheckEntryFileNameNodeTransformer::class)]
#[CoversClass(SourceFormat::class)]
final class CheckEntryFileNameNodeTransformerTest extends TestCase
{
    /** @return iterable<string, array{string, ?string, string}> */
    public static function entryFiles(): iterable
    {
        yield 'reStructuredText manual' => ['rst', 'index.rst', 'rst'];
        yield 'Markdown manual' => ['md', 'index.md', 'md'];
        yield 'both formats, entry in Markdown' => ['rst,md', 'index.md', 'md'];
        yield 'both formats, entry in reStructuredText' => ['md,rst', 'index.rst', 'rst'];
        yield 'file name unknown, the configured format' => ['md', null, 'md'];
    }

    #[DataProvider('entryFiles')]
    public function testTheWarningNamesTheFileTheManualStartsAt(string $inputFormat, ?string $originalFileName, string $extension): void
    {
        self::assertSame(
            [sprintf(
                'The manual starts at "index.%1$s". docs.typo3.org opens a manual at "Index.html", so once deployed it has no start page. Rename the file to "Index.%1$s".',
                $extension,
            )],
            $this->warningsFor($inputFormat, $originalFileName),
        );
    }

    /** @return list<string> */
    private function warningsFor(string $inputFormat, ?string $originalFileName): array
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $warnings = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->warnings[] = (string) $message;
            }
        };
        $document = (new DocumentNode('hash', 'index'))->withIsRoot(true);
        if ($originalFileName !== null) {
            $document = $document->withKeepExistingOptions(['originalFileName' => $originalFileName]);
            self::assertInstanceOf(DocumentNode::class, $document);
        }
        $projectSettings = new ProjectSettings();
        $projectSettings->setInputFormat($inputFormat);
        $context = (new CompilerContext(new ProjectNode()))->withDocumentShadowTree($document);
        $transformer = new CheckEntryFileNameNodeTransformer(new SettingsManager($projectSettings), $logger);

        $transformer->enterNode($document, $context);

        return $logger->warnings;
    }
}
