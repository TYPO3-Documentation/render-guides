<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use T3Docs\GuidesCli\Command\MigrateSettingsCommand;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Migration\Dto\ProcessingResult;
use T3Docs\GuidesCli\Migration\Exception\ProcessingException;
use T3Docs\GuidesCli\Migration\Processor;

final class MigrateSettingsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private Processor&Stub $processorStub;

    protected function setUp(): void
    {
        $this->processorStub = self::createStub(Processor::class);

        $command = new MigrateSettingsCommand($this->processorStub);
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function executeReturnsWithSuccessWhenNoGuidesXmlIsAlreadyAvailable(): void
    {
        $tmpFolder = sys_get_temp_dir();

        $this->processorStub
            ->method('process')
            ->with($tmpFolder . '/Settings.cfg', $tmpFolder . '/guides.xml')
            ->willReturn(new ProcessingResult(21, ['first message', 'second message']));

        $this->commandTester->execute([
            'input' => $tmpFolder,
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        self::assertSame(<<< EXPECTED
            Migrating /tmp/Settings.cfg to /tmp/guides.xml ...
            first message
            second message
            21 settings converted. You can now delete Settings.cfg and add guides.xml to your repository.
            EXPECTED, trim($this->commandTester->getDisplay()));
    }

    #[Test]
    public function executeReturnsWithErrorWhenGuidesXmlIsAlreadyAvailable(): void
    {
        $tmpFolder = $this->prepareFileStructure();
        touch($tmpFolder . '/guides.xml');

        $this->commandTester->execute([
            'input' => $tmpFolder,
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringStartsWith('Target file already exists in specified directory (/tmp/', trim($this->commandTester->getDisplay()));
    }

    #[Test]
    public function executeReturnsWithSuccessWhenGuidesXmlIsAlreadyAvailableAndForceOptionIsSet(): void
    {
        $tmpFolder = $this->prepareFileStructure();
        touch($tmpFolder . '/guides.xml');

        $this->processorStub
            ->method('process')
            ->with($tmpFolder . '/Settings.cfg', $tmpFolder . '/guides.xml')
            ->willReturn(new ProcessingResult(1, []));

        $this->commandTester->execute([
            'input' => $tmpFolder,
            '--force' => true,
        ]);

        $this->commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    public function executeReturnsWithErrorWhenProcessorThrowsException(): void
    {
        $tmpFolder = sys_get_temp_dir();

        $this->processorStub
            ->method('process')
            ->willThrowException(ProcessingException::xmlCannotBeGenerated());

        $this->commandTester->execute([
            'input' => $tmpFolder,
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('The XML file cannot be generated', $this->commandTester->getDisplay());
    }

    private function prepareFileStructure(): string
    {
        $folder = sys_get_temp_dir() . '/guides-cli-' . uniqid();
        mkdir($folder);

        return $folder;
    }
}
