# Performance Improvement Plan

## Phase 1: Lazy Highlighter Language Loading (P1)

### Problem
The `Brotkrueml\TwigCodeHighlight\Extension` creates a `Highlighter` instance in its constructor:
```php
$this->highlighter = new Highlighter();  // Loads ALL 185 languages
```

### Solution: Create Custom Extension Wrapper

Create a replacement extension that uses lazy language loading:

**File: `packages/typo3-docs-theme/src/Twig/LazyCodeHighlightExtension.php`**

```php
<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use Highlight\Highlighter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Lazy-loading code highlight extension.
 * Only loads language definitions when actually needed.
 */
final class LazyCodeHighlightExtension extends AbstractExtension
{
    private Highlighter $highlighter;
    private array $loadedLanguages = [];

    /** @var array<string, string> Common language aliases */
    private const LANGUAGE_ALIASES = [
        'none' => 'plaintext',
        'text' => 'plaintext',
        'shell' => 'bash',
        'console' => 'bash',
    ];

    public function __construct(
        private readonly string $classes = 'code-block',
        private readonly array $additionalLanguages = [],
    ) {
        // Create highlighter WITHOUT loading all languages
        $this->highlighter = new Highlighter(false);

        // Pre-register commonly used languages for best performance
        $this->preRegisterCommonLanguages();

        // Register additional project-specific languages
        foreach ($additionalLanguages as $lang) {
            $this->highlighter::registerLanguage($lang[0], $lang[1], $lang[2] ?? false);
            $this->loadedLanguages[$lang[0]] = true;
        }
    }

    private function preRegisterCommonLanguages(): void
    {
        $langPath = dirname((new \ReflectionClass(Highlighter::class))->getFileName())
            . '/languages/';

        // Only register languages commonly used in TYPO3 documentation
        $common = ['php', 'html', 'xml', 'json', 'yaml', 'bash', 'sql',
                   'javascript', 'css', 'plaintext', 'ini', 'diff'];

        foreach ($common as $lang) {
            $file = $langPath . $lang . '.json';
            if (is_readable($file)) {
                $this->highlighter::registerLanguage($lang, $file);
                $this->loadedLanguages[$lang] = true;
            }
        }
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('codehighlight', $this->highlight(...), ['is_safe' => ['html']]),
        ];
    }

    private function highlight(string $code, ?string $language, ...): string
    {
        $lang = self::LANGUAGE_ALIASES[$language] ?? $language ?? '';

        // Lazy-load language if not already loaded
        if ($lang !== '' && !isset($this->loadedLanguages[$lang])) {
            $this->loadLanguageOnDemand($lang);
        }

        // ... rest of highlight logic
    }

    private function loadLanguageOnDemand(string $lang): void
    {
        $langPath = dirname((new \ReflectionClass(Highlighter::class))->getFileName())
            . '/languages/' . $lang . '.json';

        if (is_readable($langPath)) {
            $this->highlighter::registerLanguage($lang, $langPath);
            $this->loadedLanguages[$lang] = true;
        }
    }
}
```

### Alternative: Composer Patch

If modifying vendor code, patch `brotkrueml/twig-codehighlight`:

```diff
--- a/src/Extension.php
+++ b/src/Extension.php
@@ -42,7 +42,7 @@ final class Extension extends AbstractExtension
         private string $classes = '',
     ) {
-        $this->highlighter = new Highlighter();
+        $this->highlighter = new Highlighter(false);
         $this->lineNumbersParser = new LineNumbersParser();
+
+        // Register only commonly used languages
+        $langPath = dirname((new \ReflectionClass(Highlighter::class))->getFileName()) . '/languages/';
+        foreach (['php', 'html', 'xml', 'json', 'yaml', 'bash', 'sql', 'javascript', 'css', 'plaintext'] as $lang) {
+            Highlighter::registerLanguage($lang, $langPath . $lang . '.json');
+        }
```

### Expected Impact
- **I/O reduction**: ~180 fewer file operations
- **Time savings**: 30-50ms per render
- **Memory savings**: Reduced language definition parsing

---

## Phase 2: Container Compilation Caching (P2)

### Problem
DI container is rebuilt from XML/PHP configuration on every CLI invocation (~150ms).

### Solution: Compiled Container Caching

**File: `packages/typo3-guides-cli/src/DependencyInjection/CachingContainerFactory.php`**

```php
<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\DependencyInjection;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

final class CachingContainerFactory
{
    private const CACHE_DIR = '/tmp/typo3-guides-container-cache';

    public function create(ContainerFactory $factory, string $vendorDir): ContainerInterface
    {
        $cacheKey = $this->computeCacheKey($vendorDir);
        $cacheFile = self::CACHE_DIR . '/' . $cacheKey . '.php';
        $containerClass = 'CompiledContainer_' . $cacheKey;

        $cache = new ConfigCache($cacheFile, false);

        if ($cache->isFresh()) {
            require_once $cacheFile;
            return new $containerClass();
        }

        // Build container the normal way
        $container = $factory->create($vendorDir);

        // Compile and cache
        $dumper = new PhpDumper($container);
        $cache->write(
            $dumper->dump(['class' => $containerClass]),
            $container->getResources()
        );

        return $container;
    }

    private function computeCacheKey(string $vendorDir): string
    {
        // Include config file hashes for invalidation
        $files = [
            $vendorDir . '/../guides.xml',
            $vendorDir . '/../Documentation/guides.xml',
        ];

        $hash = '';
        foreach ($files as $file) {
            if (is_file($file)) {
                $hash .= md5_file($file);
            }
        }

        return substr(md5($vendorDir . $hash . PHP_VERSION), 0, 16);
    }
}
```

### Expected Impact
- **Time savings**: 100-150ms on warm runs
- **First run**: Same as current (container is built and cached)
- **Subsequent runs**: Container loaded from compiled PHP

---

## Phase 3: Parallel Rendering with Fibers (P3)

### Problem
Three render passes (html, singlepage, interlink) run sequentially.

### Solution: Use PHP 8.1 Fibers for concurrent I/O

This is a larger architectural change. Each render pass primarily does:
1. Template rendering (CPU-bound)
2. File writing (I/O-bound)

With Fibers, file I/O can be overlapped:

```php
// Conceptual - requires async I/O library
$fibers = [];
foreach ($outputFormats as $format) {
    $fibers[] = new Fiber(function() use ($format, $documents, ...) {
        $this->commandBus->handle(new RenderCommand(...));
    });
}

foreach ($fibers as $fiber) {
    $fiber->start();
}

// Wait for all to complete
foreach ($fibers as $fiber) {
    while (!$fiber->isTerminated()) {
        $fiber->resume();
    }
}
```

### Expected Impact
- **Time savings**: 10-20% of render phase (~100-200ms)
- **Complexity**: High - requires async I/O support
- **Risk**: Medium - concurrent writes need coordination

---

## Implementation Order

1. **Week 1**: Implement lazy highlighter loading (P1)
   - Create LazyCodeHighlightExtension or Composer patch
   - Update DI configuration
   - Verify functionality with tests

2. **Week 2**: Implement container caching (P2)
   - Create CachingContainerFactory
   - Integrate with CLI bootstrap
   - Add cache invalidation logic

3. **Future**: Consider parallel rendering (P3)
   - Evaluate async I/O libraries (ReactPHP, Amp)
   - Prototype with Fibers
   - Benchmark improvements

---

## Verification Benchmarks

After each phase:

```bash
# Clear all caches
rm -rf var/cache /tmp/typo3-guides-*

# Run benchmark
for i in 1 2 3; do
    echo "Run $i:"
    time php bin/guides run Documentation --output /tmp/bench-$i 2>&1 | tail -1
done
```

Target improvements:
- Phase 1: Cold 1900ms → 1850ms, Warm 1300ms → 1250ms
- Phase 2: Cold 1850ms → 1700ms, Warm 1250ms → 1100ms
- Phase 3: Warm 1100ms → 950ms
