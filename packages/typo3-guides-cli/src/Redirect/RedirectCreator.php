<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Redirect;

/**
 * Creates nginx redirect configurations for moved documentation files
 */
class RedirectCreator
{
    private string $nginxRedirectFile = 'redirects.nginx.conf';

    /**
     * @param array<string, string> $movedFiles
     * @return array<string, string>
     */
    public function createRedirects(array $movedFiles, string $docsPath, string $versions, string $path): array
    {
        $createdRedirects = [];
        $nginxRedirects = [];

        foreach ($movedFiles as $oldPath => $newPath) {
            $oldRelativePath = $this->stripDocsPathPrefix($oldPath, $docsPath);
            $newRelativePath = $this->stripDocsPathPrefix($newPath, $docsPath);

            $oldUrlPath = $this->convertToUrlPath($oldRelativePath);
            $newUrlPath = $this->convertToUrlPath($newRelativePath);

            $nginxRedirects[] = sprintf("location = ^%s%s/en-us/%s { return 301 %s$1/en-us/%s; }", $path, $versions, $oldUrlPath, $path, $newUrlPath);

            $createdRedirects[$oldPath] = $newPath;
        }

        if (!empty($nginxRedirects)) {
            $nginxConfig = "# Nginx redirects for moved files in Documentation\n";
            $nginxConfig .= "# Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            $nginxConfig .= implode("\n", $nginxRedirects) . "\n";

            file_put_contents($this->nginxRedirectFile, $nginxConfig);
        }

        return $createdRedirects;
    }

    /**
     * Set a custom path for the nginx redirect configuration file
     */
    public function setNginxRedirectFile(string $filePath): void
    {
        $this->nginxRedirectFile = $filePath;
    }

    private function stripDocsPathPrefix(string $path, string $docsPath): string
    {
        if (str_starts_with($path, $docsPath . '/')) {
            return substr($path, strlen($docsPath) + 1);
        }
        return $path;
    }

    private function convertToUrlPath(string $path): string
    {
        $path = preg_replace('/\.(rst|md)$/', '.html', $path);
        if (is_string($path) === false) {
            throw new \RuntimeException('Failed to convert path to URL format');
        }

        if (basename($path) === 'Index') {
            $path = dirname($path);
            if ($path === '.') {
                $path = '';
            }
        }

        return ltrim($path, '/');
    }
}
