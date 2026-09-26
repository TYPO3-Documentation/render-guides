<?php

declare(strict_types=1);

/**
 * Cuts the recorded responses down to what the integration fixtures use.
 *
 * A recording is the whole answer: the inventory of TYPO3 Explained alone is
 * 4 MB, the API information of api.typo3.org 3 MB. A fixture uses a handful of
 * entries, so an entry is kept where the sources of the fixtures name it:
 *
 * - an inventory keeps the link targets whose key the sources contain,
 * - the API information the classes the sources name, also in a group use,
 *   and one class below each namespace they name,
 * - a Packagist answer the one version that is read.
 *
 * Run after recording, from the project root:
 *
 *     RECORD_HTTP=1 vendor/bin/phpunit --testsuite=integration
 *     php tests/Integration/Http/trim-recordings.php
 *
 * and check that the integration tests still pass without network.
 */

$root = dirname(__DIR__);
$recordings = $root . '/http-fixtures';

$sources = '';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    $path = $file->getPathname();
    if (str_contains($path, '/input/') && $file->isFile()) {
        $sources .= "\n" . file_get_contents($path);
    }
}
$lower = strtolower(str_replace('\\\\', '\\', $sources));
// Inventory keys are anchors: lower case, anything else a hyphen.
$anchors = preg_replace('/[^a-z0-9\/]+/', '-', $lower) ?? '';

function named(string $key, string $lower, string $anchors): bool
{
    return $key !== '' && (str_contains($lower, $key) || str_contains($anchors, $key));
}

/**
 * The API lists types only, and a namespace is known by a type below it. For
 * each namespace the sources name, the first type below it stays.
 *
 * @param array<string, mixed> $types
 * @return array<string, mixed>
 */
function namespaceWitnesses(array $types, string $lower): array
{
    $witnesses = [];
    foreach ($types as $fqn => $info) {
        $namespace = strtolower(ltrim((string) $fqn, '\\'));
        while (($separator = strrpos($namespace, '\\')) !== false) {
            $namespace = substr($namespace, 0, $separator);
            // "TYPO3" and "TYPO3\CMS" are named everywhere; a namespace worth
            // a witness has a package in it.
            if (substr_count($namespace, '\\') < 2 || isset($witnesses[$namespace])) {
                continue;
            }
            if (preg_match('/' . preg_quote($namespace, '/') . '(?![\w\\\\])/', $lower) === 1) {
                $witnesses[$namespace] = [$fqn => $info];
            }
        }
    }

    return array_merge(...array_values($witnesses));
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($recordings, FilesystemIterator::SKIP_DOTS)) as $file) {
    $path = $file->getPathname();
    $json = json_decode((string) file_get_contents($path), true);
    if (!is_array($json)) {
        continue;
    }
    $before = filesize($path);

    if (str_ends_with($path, '/objects.inv.json')) {
        foreach ($json as $group => $entries) {
            if (!is_array($entries)) {
                continue;
            }
            $json[$group] = array_filter(
                $entries,
                static fn(string|int $key): bool => named(strtolower((string) $key), $lower, $anchors),
                ARRAY_FILTER_USE_KEY,
            ) ?: new stdClass();
        }
    } elseif (str_ends_with($path, '/api-info.json')) {
        $json = array_filter(
            $json,
            static function (string|int $fqn) use ($lower, $anchors): bool {
                $name = strtolower(ltrim((string) $fqn, '\\'));
                $separator = strrpos($name, '\\');
                // A group use, "use Vendor\Package\{First, Second};", names the
                // namespace and the class apart.
                return named($name, $lower, $anchors) || ($separator !== false
                    && str_contains($lower, substr($name, 0, $separator) . '\\{')
                    && preg_match('/\b' . preg_quote(substr($name, $separator + 1), '/') . '\b/', $lower) === 1);
            },
            ARRAY_FILTER_USE_KEY,
        ) + namespaceWitnesses($json, $lower);
    } elseif (str_contains($path, '/repo.packagist.org/')) {
        foreach ($json['packages'] ?? [] as $name => $versions) {
            $json['packages'][$name] = array_slice($versions, 0, 1);
        }
    }

    file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    clearstatcache();
    printf("%8d -> %6d bytes  %s\n", $before, filesize($path), substr($path, strlen($recordings) + 1));
}
