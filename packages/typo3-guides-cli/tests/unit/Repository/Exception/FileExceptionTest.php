<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Repository\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Repository\Exception\FileException;

final class FileExceptionTest extends TestCase
{
    #[Test]
    public function classIsInstanceOfRuntimeException(): void
    {
        self::assertInstanceOf(\RuntimeException::class, new FileException());
    }

    #[Test]
    public function notFound(): void
    {
        $actual = FileException::notFound('/some/path/to/Settings.cfg');

        $expectedMessage = 'File "/some/path/to/Settings.cfg" cannot be found!';

        self::assertInstanceOf(FileException::class, $actual);
        self::assertSame($expectedMessage, $actual->getMessage());
    }

    #[Test]
    public function notReadable(): void
    {
        $actual = FileException::notReadable('/some/path/to/Settings.cfg');

        $expectedMessage = 'File "/some/path/to/Settings.cfg" cannot be read!';

        self::assertInstanceOf(FileException::class, $actual);
        self::assertSame($expectedMessage, $actual->getMessage());
    }

    #[Test]
    public function notParsable(): void
    {
        $actual = FileException::notParsable('/some/path/to/Settings.cfg');

        $expectedMessage = 'File "/some/path/to/Settings.cfg" cannot be parsed!';

        self::assertInstanceOf(FileException::class, $actual);
        self::assertSame($expectedMessage, $actual->getMessage());
    }
}
