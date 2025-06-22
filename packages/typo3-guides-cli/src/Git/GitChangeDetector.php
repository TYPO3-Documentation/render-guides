<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Git;

/**
 * Detects file changes in git, specifically focusing on moved files
 */
class GitChangeDetector
{
    /** @return array<string, string> */
    public function detectMovedFiles(string $baseBranch, string $docsPath): array
    {
        $movedFiles = [];

        // Get the common ancestor commit between current branch and base branch
        $mergeBase = trim($this->executeGitCommand("merge-base {$baseBranch} HEAD"));

        if (empty($mergeBase)) {
            throw new \RuntimeException('Could not determine merge base with the specified branch.');
        }

        // Use git diff to find renamed files
        // --diff-filter=R shows only renamed files
        // -M detects renames
        // --name-status shows the status and filenames
        $command = "diff {$mergeBase} HEAD --diff-filter=R -M --name-status";
        $output = $this->executeGitCommand($command);

        // Parse the output to extract renamed files
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Format is: R<score>\t<old-file>\t<new-file>
            $parts = preg_split('/\s+/', $line, 3);
            if ($parts === false) {
                continue;
            }

            if (count($parts) !== 3 || !str_starts_with($parts[0], 'R')) {
                continue;
            }

            $oldPath = trim($parts[1]);
            $newPath = trim($parts[2]);

            if ($this->isDocumentationFile($oldPath, $docsPath) && $this->isDocumentationFile($newPath, $docsPath)) {
                $movedFiles[$oldPath] = $newPath;
            }
        }

        return $movedFiles;
    }

    private function isDocumentationFile(string $filePath, string $docsPath): bool
    {
        return str_starts_with($filePath, $docsPath)
            && (str_ends_with($filePath, '.rst') || str_ends_with($filePath, '.md'));
    }

    private function executeGitCommand(string $command): string
    {
        $fullCommand = "git {$command} 2>&1";
        $output = [];
        $returnCode = 0;

        exec($fullCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException('Git command failed: ' . implode("\n", $output));
        }

        return implode("\n", $output);
    }
}
