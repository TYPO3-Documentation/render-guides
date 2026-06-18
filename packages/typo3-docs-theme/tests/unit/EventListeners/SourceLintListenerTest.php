<?php

declare(strict_types=1);

use phpDocumentor\Guides\Event\PreParseDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use T3Docs\Typo3DocsTheme\Lint\SourceLintRule;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class SourceLintListenerTest extends TestCase
{
    #[Test]
    public function emitsNothingWhenLintIsDisabled(): void
    {
        $logger = self::spyLogger();
        $rule = self::rule(['should not run']);
        $listener = new \T3Docs\Typo3DocsTheme\EventListeners\SourceLintListener(
            new Typo3DocsThemeSettings([]),
            $logger,
            [$rule],
        );

        $listener(self::event('whatever'));

        self::assertSame([], $logger->warnings);
    }

    #[Test]
    public function runsEveryRuleAndLogsWithFileContextWhenEnabled(): void
    {
        $logger = self::spyLogger();
        $listener = new \T3Docs\Typo3DocsTheme\EventListeners\SourceLintListener(
            new Typo3DocsThemeSettings(['lint' => 'true']),
            $logger,
            [self::rule(['first finding']), self::rule(['second finding', 'third finding'])],
        );

        $listener(self::event('content', 'Foo/Bar.rst'));

        self::assertSame(['first finding', 'second finding', 'third finding'], $logger->warnings);
        // No "[filename]" prefix in the message; the file lives in the log context instead.
        self::assertSame([['rst-file' => 'Foo/Bar.rst'], ['rst-file' => 'Foo/Bar.rst'], ['rst-file' => 'Foo/Bar.rst']], $logger->contexts);
    }

    /** @param list<string> $warnings */
    private static function rule(array $warnings): SourceLintRule
    {
        return new class ($warnings) implements SourceLintRule {
            /** @param list<string> $warnings */
            public function __construct(private readonly array $warnings) {}

            public function lint(string $contents): array
            {
                return $this->warnings;
            }
        };
    }

    private static function event(string $contents, string $fileName = 'index.rst'): PreParseDocument
    {
        // PreParseDocument's Parser dependency is final (unstubbable) and the listener
        // never uses it, so build the event without the constructor and set only the
        // two fields the listener reads.
        $reflection = new ReflectionClass(PreParseDocument::class);
        $event = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('fileName')->setValue($event, $fileName);
        $reflection->getProperty('contents')->setValue($event, $contents);

        return $event;
    }

    private static function spyLogger(): AbstractLogger
    {
        return new class () extends AbstractLogger {
            /** @var list<string> */
            public array $warnings = [];
            /** @var list<mixed[]> */
            public array $contexts = [];

            /** @param mixed[] $context */
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                if ($level === 'warning') {
                    $this->warnings[] = (string) $message;
                    $this->contexts[] = $context;
                }
            }
        };
    }
}
