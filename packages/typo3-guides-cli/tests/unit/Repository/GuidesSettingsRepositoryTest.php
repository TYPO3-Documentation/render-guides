<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Repository\Exception\FileException;
use T3Docs\GuidesCli\Repository\GuidesSettingsRepository;

final class GuidesSettingsRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->subject = new GuidesSettingsRepository();
    }

    #[Test]
    public function createWritesFileCorrectly(): void
    {
        $filePath = sys_get_temp_dir() . '/cli_guides_test_' . uniqid() . '_guides.xml';
        $document = $this->buildDocument();

        $actual = $this->subject->create($filePath, $document);
        $expected = strlen($document->saveXML());

        self::assertSame($expected, $actual);
        self::assertFileExists($filePath);
        self::assertXmlStringEqualsXmlFile($filePath, '<?xml version="1.0" encoding="UTF-8"?><guides/>');
    }

    #[Test]
    public function createThrowsExceptionIfFileCannotBeWritten(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/not.*written/');

        $filePath = sys_get_temp_dir() . '/cli_guides_test_' . uniqid() . '/guides.xml';
        $document = $this->buildDocument();

        $this->subject->create($filePath, $document);
    }

    private function buildDocument(): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $guides = $document->createElement('guides');
        $document->appendChild($guides);

        return $document;
    }
}
