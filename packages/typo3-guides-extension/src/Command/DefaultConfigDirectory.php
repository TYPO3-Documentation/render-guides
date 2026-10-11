<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Command;

use function array_slice;
use function count;
use function in_array;
use function is_file;
use function rtrim;
use function str_contains;
use function str_starts_with;

/**
 * The directory whose guides.xml is loaded when no "--config" is passed: the
 * directory being rendered, "Documentation" when none is named.
 *
 * guides loads its configuration before it knows what it renders: the
 * container is built from guides.xml, and the command that reads the input
 * argument comes out of that container. Without "--config", it loads the
 * guides.xml of the working directory only, and a manual renders without its
 * theme settings and interlinks. So the binary reads the input from the raw
 * command line first.
 */
final class DefaultConfigDirectory
{
    /**
     * Options of "run" whose value may follow as a token of its own,
     * "--output out". The value is not the input directory.
     */
    public const VALUE_OPTIONS = [
        '--config', '-c', '--working-dir', '-w',
        '--output', '--input-file', '--exclude-path', '--input-format',
        '--output-format', '--theme', '--log-path', '--localization',
        '--host', '--port',
    ];

    private const DEFAULT_INPUT_DIRECTORY = 'Documentation';

    /**
     * @param list<string> $argv The command line, the script first
     *
     * @return string|null The directory to pass as "--config", or null to
     *   leave the command line as it is
     */
    public static function find(array $argv, string $workingDirectory): ?string
    {
        $positional = [];
        $tokens = array_slice($argv, 1);
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if ($token === '--') {
                $positional = [...$positional, ...array_slice($tokens, $i + 1)];
                break;
            }

            if (in_array($token, ['--config', '-c'], true) || str_starts_with($token, '--config=') || (str_starts_with($token, '-c') && $token !== '-c')) {
                return null;
            }

            if (str_starts_with($token, '-')) {
                if (!str_contains($token, '=') && in_array($token, self::VALUE_OPTIONS, true)) {
                    $i++;
                }

                continue;
            }

            $positional[] = $token;
        }

        // "guides run Docs" and "guides Docs" render the same directory
        if (($positional[0] ?? null) === 'run') {
            $positional = array_slice($positional, 1);
        }

        $input = $positional[0] ?? self::DEFAULT_INPUT_DIRECTORY;
        $directory = str_starts_with($input, '/') ? $input : rtrim($workingDirectory, '/') . '/' . $input;

        return is_file($directory . '/guides.xml') ? $directory : null;
    }
}
