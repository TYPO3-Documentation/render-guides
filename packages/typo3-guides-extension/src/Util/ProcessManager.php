<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Util;

use RuntimeException;

/**
 * Utility for managing forked child processes with timeouts and cleanup.
 *
 * Provides consistent process management across all parallel processing classes:
 * - Non-blocking wait with configurable timeout
 * - SIGTERM handling for cleanup
 * - Secure temp file creation with proper permissions
 */
final class ProcessManager
{
    /** Default timeout in seconds for waiting on child processes */
    public const int DEFAULT_TIMEOUT_SECONDS = 300;

    /** Poll interval in microseconds (10ms) */
    private const int POLL_INTERVAL_USEC = 10000;

    /** @var list<string> Temp files to clean on shutdown */
    private static array $tempFilesToClean = [];

    /** @var bool Whether shutdown handler is registered */
    private static bool $shutdownRegistered = false;

    /**
     * Wait for all child processes with timeout.
     *
     * Uses non-blocking WNOHANG to poll process status, allowing timeout detection.
     * Sends SIGKILL to stuck processes after timeout expires.
     *
     * @param array<int, int> $childPids Map of workerId => pid
     * @param int $timeoutSeconds Maximum time to wait (default 300s)
     * @return array{successes: list<int>, failures: array<int, string>}
     * @throws RuntimeException If all workers fail
     */
    public static function waitForChildrenWithTimeout(
        array $childPids,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ): array {
        $startTime = time();
        $remaining = $childPids;
        $successes = [];
        $failures = [];

        while ($remaining !== []) {
            foreach ($remaining as $workerId => $pid) {
                $status = 0;
                $result = pcntl_waitpid($pid, $status, WNOHANG);

                if ($result === 0) {
                    // Still running
                    continue;
                }

                if ($result === -1) {
                    // Error - child doesn't exist
                    $failures[$workerId] = 'waitpid failed';
                    unset($remaining[$workerId]);
                    continue;
                }

                // Child exited
                unset($remaining[$workerId]);

                // $status is always set to int by pcntl_waitpid when result > 0
                assert(is_int($status));

                if (pcntl_wifexited($status)) {
                    $exitCode = pcntl_wexitstatus($status);
                    if ($exitCode === 0) {
                        $successes[] = $workerId;
                    } else {
                        $failures[$workerId] = sprintf('exit code %d', $exitCode);
                    }
                } elseif (pcntl_wifsignaled($status)) {
                    $signal = pcntl_wtermsig($status);
                    $failures[$workerId] = sprintf('killed by signal %d', $signal);
                }
            }

            // Check timeout
            if (time() - $startTime > $timeoutSeconds) {
                // Kill remaining children
                foreach ($remaining as $workerId => $pid) {
                    posix_kill($pid, SIGKILL);
                    pcntl_waitpid($pid, $status); // Reap zombie
                    $failures[$workerId] = sprintf('killed after %ds timeout', $timeoutSeconds);
                }
                break;
            }

            // Don't spin-wait if processes still running
            if ($remaining !== []) {
                usleep(self::POLL_INTERVAL_USEC);
            }
        }

        return ['successes' => $successes, 'failures' => $failures];
    }

    /**
     * Create a secure temp file with restricted permissions.
     *
     * Creates temp file with 0600 permissions to prevent other users from reading.
     * Registers file for cleanup on shutdown/signal.
     *
     * @param string $prefix Temp file prefix
     * @return string|false Path to temp file, or false on failure
     */
    public static function createSecureTempFile(string $prefix): string|false
    {
        self::ensureShutdownHandler();

        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        if ($tempFile === false) {
            return false;
        }

        // Set restrictive permissions (owner read/write only)
        chmod($tempFile, 0o600);

        // Register for cleanup
        self::$tempFilesToClean[] = $tempFile;

        return $tempFile;
    }

    /**
     * Remove a temp file from cleanup list (already cleaned).
     */
    public static function unregisterTempFile(string $tempFile): void
    {
        $key = array_search($tempFile, self::$tempFilesToClean, true);
        if ($key !== false) {
            unset(self::$tempFilesToClean[$key]);
            self::$tempFilesToClean = array_values(self::$tempFilesToClean);
        }
    }

    /**
     * Clean up a temp file and unregister it.
     */
    public static function cleanupTempFile(string $tempFile): void
    {
        @unlink($tempFile);
        self::unregisterTempFile($tempFile);
    }

    /**
     * Clear temp file tracking list.
     *
     * Call this in child processes after fork to prevent them from cleaning up
     * temp files that belong to the parent process when they exit.
     */
    public static function clearTempFileTracking(): void
    {
        self::$tempFilesToClean = [];
    }

    /**
     * Ensure shutdown and signal handlers are registered.
     *
     * Note: Handlers are only registered when pcntl is available (CLI mode)
     * and we're not in a test environment, to avoid conflicts with PHPUnit.
     */
    private static function ensureShutdownHandler(): void
    {
        if (self::$shutdownRegistered) {
            return;
        }

        // Skip handler registration in test environments
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            self::$shutdownRegistered = true;
            return;
        }

        // Register shutdown function for normal termination
        register_shutdown_function([self::class, 'cleanupAllTempFiles']);

        // Register signal handlers if pcntl available
        if (function_exists('pcntl_signal')) {
            // SIGTERM - graceful termination
            pcntl_signal(SIGTERM, [self::class, 'handleSignal']);
            // SIGINT - Ctrl+C
            pcntl_signal(SIGINT, [self::class, 'handleSignal']);
        }

        self::$shutdownRegistered = true;
    }

    /**
     * Handle termination signals by cleaning up temp files.
     */
    public static function handleSignal(int $signal): void
    {
        self::cleanupAllTempFiles();
        exit(128 + $signal);
    }

    /**
     * Clean up all registered temp files.
     */
    public static function cleanupAllTempFiles(): void
    {
        foreach (self::$tempFilesToClean as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        self::$tempFilesToClean = [];
    }
}
