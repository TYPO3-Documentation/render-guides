<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use T3Docs\GuidesExtension\Command\RunDecorator;

final class RunDecoratorTest extends TestCase
{
    /** @return iterable<string, array{string, int, string}> */
    public static function subProcessProvider(): iterable
    {
        yield 'a warning on stderr with exit code 0 passes' => [
            'fwrite(STDERR, "app.WARNING: a warning"); exit(0);',
            Command::SUCCESS,
            'app.WARNING: a warning',
        ];
        yield 'exit code 1 fails' => [
            'echo "Rendering failed"; exit(1);',
            Command::FAILURE,
            'Rendering failed',
        ];
        yield 'exit code 1 without output fails' => [
            'exit(1);',
            Command::FAILURE,
            '',
        ];
        yield 'exit code 0 without output passes' => [
            'exit(0);',
            Command::SUCCESS,
            '',
        ];
    }

    #[DataProvider('subProcessProvider')]
    public function testResultFollowsTheExitCodeOfTheSubProcess(string $code, int $expected, string $expectedOutput): void
    {
        $output = new BufferedOutput();

        $result = RunDecorator::runSubProcess(new Process([PHP_BINARY, '-r', $code]), $output);

        self::assertSame($expected, $result);
        self::assertStringContainsString($expectedOutput, $output->fetch());
    }
}
