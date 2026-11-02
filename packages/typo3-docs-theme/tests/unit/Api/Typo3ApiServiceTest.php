<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * A render that fails on warnings must not fail because api.typo3.org did
 * not answer, unless the page would be published without content.
 */
#[CoversClass(Typo3ApiService::class)]
final class Typo3ApiServiceTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    public function testAnApiThatDoesNotAnswerIsANotice(): void
    {
        $service = $this->service(static fn(): false => false);

        self::assertSame([], $service->getClassInfo('\TYPO3\CMS\Core\Site\SiteFinder'));
        self::assertCount(1, $this->log);
        self::assertStringStartsWith('notice: TYPO3 API could not be loaded from https://api.typo3.org/main/api-info.json, api.typo3.org did not answer.', $this->log[0]);
    }

    public function testAnApiThatIsNotFoundIsANotice(): void
    {
        $service = $this->service(static fn(): null => null);

        self::assertSame([], $service->getClassInfo('\TYPO3\CMS\Core\Site\SiteFinder'));
        self::assertCount(1, $this->log);
        self::assertStringStartsWith('notice: TYPO3 API could not be loaded from https://api.typo3.org/main/api-info.json, it was not found.', $this->log[0]);
    }

    public function testABrokenApiIsANotice(): void
    {
        $service = $this->service(static fn(): string => '{"TYPO3');

        self::assertSame([], $service->getClassInfo('\TYPO3\CMS\Core\Site\SiteFinder'));
        self::assertCount(1, $this->log);
        self::assertStringStartsWith('notice: ', $this->log[0]);
    }

    public function testATypeTheApiDoesNotKnowHasNoFailure(): void
    {
        $service = $this->service(static fn(): null => null);

        self::assertNull($service->getTypeApi('\TYPO3\CMS\Core\DoesNotExist'));
        self::assertSame('', $service->getTypeFailure('\TYPO3\CMS\Core\DoesNotExist'));
        self::assertSame([], $this->log);
    }

    public function testATypeThatDoesNotAnswerSaysWhy(): void
    {
        $service = $this->service(static fn(): false => false);

        self::assertNull($service->getTypeApi('\TYPO3\CMS\Core\Site\SiteFinder'));
        self::assertSame(
            'https://api.typo3.org/main/classes/TYPO3/CMS/Core/Site/SiteFinder.json could not be loaded, api.typo3.org did not answer. Render again later.',
            $service->getTypeFailure('TYPO3\CMS\Core\Site\SiteFinder'),
        );
        self::assertSame([], $this->log);
    }

    public function testATypeInAnotherFormatSaysWhy(): void
    {
        $service = $this->service(static fn(): string => '{"schema": 2}');

        self::assertNull($service->getTypeApi('\TYPO3\CMS\Core\Site\SiteFinder'));
        self::assertSame(
            'https://api.typo3.org/main/classes/TYPO3/CMS/Core/Site/SiteFinder.json is not in the format version 1 that this theme reads.',
            $service->getTypeFailure('\TYPO3\CMS\Core\Site\SiteFinder'),
        );
    }

    /** @param callable(string): (string|false|null) $fetcher */
    private function service(callable $fetcher): Typo3ApiService
    {
        $logger = new class ($this->log) extends AbstractLogger {
            /** @param list<string> $log */
            public function __construct(private array &$log) {}

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->log[] = $level . ': ' . $message;
            }
        };
        $service = new Typo3ApiService($logger, new Typo3VersionService(new Typo3DocsThemeSettings(['typo3_core_preferred' => 'main'])));
        $service->fetchWith($fetcher);

        return $service;
    }
}
