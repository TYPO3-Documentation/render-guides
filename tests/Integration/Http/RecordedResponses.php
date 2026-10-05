<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration\Http;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_dir;
use function mkdir;
use function parse_url;
use function preg_match;
use function stream_context_create;

/**
 * What the network answered once, for the integration tests to read instead.
 *
 * A fixture that links to another manual, names a PHP class or a Composer
 * package fetches from docs.typo3.org, api.typo3.org or Packagist. Read live,
 * a test failed whenever one of them was slow or down, and its expectation
 * changed whenever they did. Each answer is kept below "http-fixtures/" as
 * "<host>/<path>", and an address without one is answered as not found -- so
 * a missing recording shows as a failing test, never as a request.
 *
 * RECORD_HTTP=1 fetches what is missing and keeps it. A recording may then be
 * cut down to what the fixtures use, which keeps the files small.
 */
final class RecordedResponses
{
    public function __construct(
        private readonly string $directory,
    ) {}

    /** The body that was recorded for the address, or null for none. */
    public function __invoke(string $url): ?string
    {
        $file = $this->file($url);
        if ($file === null) {
            return null;
        }
        if (file_exists($file)) {
            $body = file_get_contents($file);

            return $body === false ? null : $body;
        }

        return getenv('RECORD_HTTP') === '1' ? $this->record($url, $file) : null;
    }

    private function file(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($host) || !is_string($path) || preg_match('#(^|/)\.\.(/|$)#', $path) === 1) {
            return null;
        }

        return $this->directory . '/' . $host . $path;
    }

    private function record(string $url, string $file): ?string
    {
        $body = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => false]]));
        if ($body === false) {
            return null;
        }
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0o777, true);
        }
        file_put_contents($file, $body);

        return $body;
    }
}
