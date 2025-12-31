<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Pipeline;

use phpDocumentor\FileSystem\FileSystem;

use function explode;
use function in_array;
use function pathinfo;
use function preg_match;
use function preg_match_all;
use function rtrim;
use function trim;

/**
 * Fast scanner to extract document order from toctree directives.
 *
 * This does a lightweight regex-based scan of .rst files to find toctree
 * directives and build the document order. This is much faster than full
 * parsing and sufficient for pre-computing navigation order.
 *
 * The order is depth-first traversal of the toctree structure, which matches
 * how prev/next navigation works in the rendered documentation.
 */
final class ToctreeOrderScanner
{
    /**
     * Regex to find toctree directive and its content.
     * Matches: .. toctree:: followed by indented content
     */
    private const string TOCTREE_PATTERN = '/^\.\.\s+toctree::\s*\n((?:[ \t]+.*\n?)*)/m';

    /**
     * Scan filesystem and return documents in toctree order.
     *
     * @param string $directory Base directory to scan
     * @param string $indexName Name of index file (without extension)
     * @param string $extension File extension (e.g., 'rst')
     * @return string[] Document paths in toctree order (without extension)
     */
    public function scan(
        FileSystem $filesystem,
        string $directory,
        string $indexName = 'Index',
        string $extension = 'rst',
    ): array {
        $allFiles = $this->collectAllFiles($filesystem, $directory, $extension);
        $toctrees = $this->extractToctrees($filesystem, $allFiles, $extension);

        // Build order starting from index
        $startFile = rtrim($directory, '/') !== ''
            ? rtrim($directory, '/') . '/' . $indexName
            : $indexName;

        $order = [];
        $visited = [];
        $this->buildOrder($startFile, $toctrees, $order, $visited, $allFiles);

        // Add any orphan files not in toctree
        foreach ($allFiles as $file) {
            if (!in_array($file, $order, true)) {
                $order[] = $file;
            }
        }

        return $order;
    }

    /**
     * Collect all files with given extension.
     *
     * @return string[] File paths without extension
     */
    private function collectAllFiles(
        FileSystem $filesystem,
        string $directory,
        string $extension,
    ): array {
        $files = [];

        try {
            $contents = $filesystem->listContents($directory, true);
            foreach ($contents as $item) {
                if ($item['type'] !== 'file') {
                    continue;
                }

                $path = isset($item['path']) && is_string($item['path']) ? $item['path'] : '';
                if ($path !== '' && pathinfo($path, PATHINFO_EXTENSION) === $extension) {
                    // Remove extension for document path
                    $files[] = substr($path, 0, -strlen('.' . $extension));
                }
            }
        } catch (\Throwable) {
            // Silently fail - we'll just have no files
        }

        return $files;
    }

    /**
     * Extract toctree entries from all files.
     *
     * @param string[] $files
     * @return array<string, string[]> Map of file -> toctree entries
     */
    private function extractToctrees(
        FileSystem $filesystem,
        array $files,
        string $extension,
    ): array {
        $toctrees = [];

        foreach ($files as $file) {
            $filePath = $file . '.' . $extension;

            try {
                $content = $filesystem->read($filePath);
                if ($content === false) {
                    continue;
                }
                $entries = $this->parseToctreeEntries($content, $file);
                if ($entries !== []) {
                    $toctrees[$file] = $entries;
                }
            } catch (\Throwable) {
                // Skip files we can't read
            }
        }

        return $toctrees;
    }

    /**
     * Parse toctree entries from file content.
     *
     * @return string[] Resolved document paths
     */
    private function parseToctreeEntries(string $content, string $currentFile): array
    {
        $entries = [];

        if (preg_match_all(self::TOCTREE_PATTERN, $content, $matches) > 0) {
            foreach ($matches[1] as $toctreeContent) {
                // Parse each line of the toctree
                $lines = explode("\n", $toctreeContent);
                foreach ($lines as $line) {
                    $line = trim($line);

                    // Skip empty lines and options (lines starting with :)
                    if ($line === '' || str_starts_with($line, ':')) {
                        continue;
                    }

                    // Handle explicit title syntax: Title <path>
                    if (preg_match('/<([^>]+)>\s*$/', $line, $m) === 1) {
                        $line = trim($m[1]);
                    }

                    // Skip external links
                    if (str_starts_with($line, 'http://') || str_starts_with($line, 'https://')) {
                        continue;
                    }

                    // Resolve relative path
                    $resolved = $this->resolvePath($currentFile, $line);
                    if ($resolved !== null) {
                        $entries[] = $resolved;
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * Resolve a toctree entry path relative to current file.
     */
    private function resolvePath(string $currentFile, string $entry): ?string
    {
        // Remove leading/trailing slashes
        $entry = trim($entry, '/');

        if ($entry === '') {
            return null;
        }

        // Get directory of current file
        $currentDir = pathinfo($currentFile, PATHINFO_DIRNAME);
        if ($currentDir === '.') {
            $currentDir = '';
        }

        // Handle absolute paths (start from root)
        if (str_starts_with($entry, '/')) {
            return ltrim($entry, '/');
        }

        // Handle relative paths
        if ($currentDir !== '') {
            return $currentDir . '/' . $entry;
        }

        return $entry;
    }

    /**
     * Build document order via depth-first traversal of toctree.
     *
     * @param array<string, string[]> $toctrees
     * @param string[] $order
     * @param array<string, bool> $visited
     * @param string[] $allFiles
     */
    private function buildOrder(
        string $file,
        array $toctrees,
        array &$order,
        array &$visited,
        array $allFiles,
    ): void {
        // Avoid cycles
        if (isset($visited[$file])) {
            return;
        }
        $visited[$file] = true;

        // Only add if file exists
        if (in_array($file, $allFiles, true)) {
            $order[] = $file;
        }

        // Recurse into toctree children
        if (isset($toctrees[$file])) {
            foreach ($toctrees[$file] as $child) {
                $this->buildOrder($child, $toctrees, $order, $visited, $allFiles);
            }
        }
    }
}
