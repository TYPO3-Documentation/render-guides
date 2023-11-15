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

    $tests = [];

    foreach ($finder as $dir) {
        if (!file_exists($dir->getPathname() . '/input')) {
            copyTests($dir->getPathname());
            continue;
        }
        $tempDir = $dir->getPathname() . '/temp';
        $expectedDir = $dir->getPathname() . '/expected';
        if (!file_exists($tempDir)) {
            echo "Skipped $dir - temp directory does not exist\n";
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
                copy($tempFile, $outputFile);
                echo "Updated $outputFile\n";
            } else {
                echo "Skipped $outputFile - $tempFile does not exist\n";
            }
        }
    }
}
