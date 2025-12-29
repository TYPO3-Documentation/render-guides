<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use phpDocumentor\Guides\Twig\Theme\ThemeManager;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;

use function is_dir;
use function mkdir;
use function sys_get_temp_dir;

/**
 * Factory that creates a Twig Environment with template caching enabled.
 *
 * This significantly improves render performance by caching compiled
 * Twig templates to disk, avoiding re-compilation on each render.
 */
final class CachedEnvironmentFactory
{
    private const string CACHE_DIR = 'typo3-guides-twig-cache';

    /**
     * @param iterable<ExtensionInterface> $extensions
     */
    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly iterable $extensions,
        private readonly string $cacheDir = '',
    ) {}

    public function __invoke(): Environment
    {
        $cacheDir = $this->getCacheDir();
        $this->ensureCacheDir($cacheDir);

        $environment = new Environment(
            $this->themeManager->getFilesystemLoader(),
            [
                'debug' => false,
                'cache' => $cacheDir,
                'auto_reload' => true, // Recompile when template changes
            ],
        );

        // Still add debug extension for potential dump() usage, but without debug mode
        // the dump() function will just not output anything
        $environment->addExtension(new DebugExtension());

        foreach ($this->extensions as $extension) {
            $environment->addExtension($extension);
        }

        return $environment;
    }

    private function getCacheDir(): string
    {
        if ($this->cacheDir !== '') {
            return $this->cacheDir;
        }

        return sys_get_temp_dir() . '/' . self::CACHE_DIR;
    }

    private function ensureCacheDir(string $cacheDir): void
    {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0o755, true);
        }
    }
}
