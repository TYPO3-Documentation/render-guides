<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ViewHelperIndex;

/**
 * The ViewHelpers the manual being rendered documents itself, by the name a
 * template writes, with what a ":fluid:" role naming one tells about it.
 *
 * Filled after parsing, from every page, before the roles are described.
 * @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CollectViewHelpersTransformer
 *
 * @phpstan-import-type ViewHelper from ExternalViewHelpers
 */
final class LocalViewHelpers
{
    /** @var array<string, ViewHelper> */
    private array $viewHelpers = [];

    /** @param ViewHelper $viewHelper */
    public function add(string $name, array $viewHelper): void
    {
        $this->viewHelpers[$name] ??= $viewHelper;
    }

    /** @return ViewHelper|null */
    public function find(string $name): ?array
    {
        return $this->viewHelpers[$name] ?? null;
    }
}
