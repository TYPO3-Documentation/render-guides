<?php

declare(strict_types=1);

namespace T3Docs\VersionHandling\Tests\Unit\Packagist;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\VersionHandling\Packagist\PackagistService;

use function array_shift;
use function json_encode;

#[CoversClass(PackagistService::class)]
final class PackagistServiceTest extends TestCase
{
    private const PACKAGE = 'vendor/package';

    #[Test]
    public function aPackagePackagistKnowsIsFound(): void
    {
        $service = $this->serviceAnswering([$this->answer()]);

        $package = $service->getComposerInfo(self::PACKAGE);

        self::assertSame('found', $package->getPackagistStatus());
        self::assertSame('A package', $package->getDescription());
    }

    #[Test]
    public function aPackagePackagistDoesNotKnowIsNotFound(): void
    {
        $service = $this->serviceAnswering([null]);

        self::assertSame('not found', $service->getComposerInfo(self::PACKAGE)->getPackagistStatus());
    }

    #[Test]
    public function aPackagistThatDoesNotAnswerIsUnreachableAfterASecondAttempt(): void
    {
        $requests = 0;
        $service = $this->serviceAnswering([false, false], $requests);

        self::assertSame(PackagistService::STATUS_UNREACHABLE, $service->getComposerInfo(self::PACKAGE)->getPackagistStatus());
        self::assertSame(2, $requests);
    }

    #[Test]
    public function aSecondAttemptThatIsAnsweredFindsThePackage(): void
    {
        $requests = 0;
        $service = $this->serviceAnswering([false, $this->answer()], $requests);

        self::assertSame('found', $service->getComposerInfo(self::PACKAGE)->getPackagistStatus());
        self::assertSame(2, $requests);
    }

    #[Test]
    public function anAnswerThatIsNoJsonIsUnreachable(): void
    {
        $service = $this->serviceAnswering(['<html>502 Bad Gateway</html>']);

        self::assertSame(PackagistService::STATUS_UNREACHABLE, $service->getComposerInfo(self::PACKAGE)->getPackagistStatus());
    }

    #[Test]
    public function anUnreachablePackagistIsNotAskedAgain(): void
    {
        $requests = 0;
        $service = $this->serviceAnswering([false, false, $this->answer()], $requests);

        $service->getComposerInfo(self::PACKAGE);

        self::assertSame(PackagistService::STATUS_UNREACHABLE, $service->getComposerInfo('vendor/other')->getPackagistStatus());
        self::assertSame(2, $requests);
    }

    #[Test]
    public function aPackageIsAskedForOnce(): void
    {
        $requests = 0;
        $service = $this->serviceAnswering([$this->answer()], $requests);

        $service->getComposerInfo(self::PACKAGE);
        $service->getComposerInfo(self::PACKAGE);

        self::assertSame(1, $requests);
    }

    /**
     * @param list<string|false|null> $answers what Packagist answers, one request after the other
     */
    private function serviceAnswering(array $answers, int &$requests = 0): PackagistService
    {
        $service = new PackagistService();
        $service->fetchWith(static function () use (&$answers, &$requests): string|false|null {
            $requests++;

            return $answers === [] ? false : array_shift($answers);
        });

        return $service;
    }

    private function answer(): string
    {
        return (string) json_encode(['packages' => [self::PACKAGE => [[
            'name' => self::PACKAGE,
            'description' => 'A package',
        ]]]]);
    }
}
