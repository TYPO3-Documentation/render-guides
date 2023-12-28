<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Migration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Migration\Exception\ProcessingException;

final class ProcessingExceptionTest extends TestCase
{
    #[Test]
    public function fileCannotBeCreated(): void
    {
        $actual = ProcessingException::fileCannotBeCreated('/path/to/some/file.xml');

        self::assertInstanceOf(ProcessingException::class, $actual);
        self::assertSame('Could not create file "/path/to/some/file.xml"', $actual->getMessage());
    }

    #[Test]
    public function xmlCannotBeGenerated(): void
    {
        $actual = ProcessingException::xmlCannotBeGenerated();

        self::assertInstanceOf(ProcessingException::class, $actual);
        self::assertSame('The XML file cannot be generated', $actual->getMessage());
    }
}
