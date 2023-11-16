<?php

require_once __DIR__ . '/../vendor/autoload.php';

copyTests(__DIR__ . '/../tests');

function copyTests(string $directory): void
{
    $finder = new \Symfony\Component\Finder\Finder();
    $finder
        ->directories()
        ->in($directory)
        ->depth('== 0');

    foreach ($finder as $dir) {
        if (!file_exists($dir->getPathname() . '/input')) {
            copyTests($dir->getPathname());
            continue;
        }
        $tempDir = $dir->getPathname() . '/temp';
        $expectedDir = $dir->getPathname() . '/expected';
        if (!file_exists($tempDir)) {
            echo sprintf("Skipped %s - temp directory does not exist\n", $dir);
            continue;
        }

        $fileFinder = new \Symfony\Component\Finder\Finder();
        $fileFinder
            ->files()
            ->in($dir->getPathname() . '/expected');
        foreach ($fileFinder as $file) {
            $fileName = $file->getFilename();
            $tempFile = $tempDir . '/' . $fileName;
            $outputFile = $expectedDir . '/' . $fileName;
            if (file_exists($tempFile)) {
                if (pathinfo($tempFile, PATHINFO_EXTENSION) === 'html') {
                    // Handle HTML file with content markers
                    copyHtmlWithMarkers($tempFile, $outputFile);
                } else {
                    // Copy non-HTML files as-is
                    copy($tempFile, $outputFile);
                    echo sprintf("Updated %s\n", $outputFile);
                }
                echo sprintf("Updated %s\n", $outputFile);
            } else {
                echo sprintf("Skipped %s - %s does not exist\n", $outputFile, $tempFile);
            }
        }
    }
}

/**
 * Copy HTML file with content markers.
 */
function copyHtmlWithMarkers(string $sourceFile, string $destinationFile): void
{
    $startMarker = '<!-- content start -->';
    $endMarker = '<!-- content end -->';

    $fileContent = file_get_contents($sourceFile);
    assert(is_string($fileContent));
    $startPos = strpos($fileContent, $startMarker);
    $endPos = strpos($fileContent, $endMarker, $startPos + strlen($startMarker));

    if ($startPos === false || $endPos === false) {
        echo sprintf("Skipped %s - Start or end marker not found\n", $destinationFile);
        return;
    }

    $contentBetweenMarkers = substr($fileContent, $startPos, $endPos + strlen($endMarker) - $startPos);
    file_put_contents($destinationFile, $contentBetweenMarkers);
    echo sprintf("Updated %s\n", $destinationFile);
}
