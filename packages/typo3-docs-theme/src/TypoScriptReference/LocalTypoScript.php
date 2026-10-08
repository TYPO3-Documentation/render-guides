<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

/**
 * The options and object types the manual being rendered documents itself,
 * with what a ":typoscript:" or ":tsconfig:" role naming one tells about it.
 * That is the TypoScript reference, which names them more often than any
 * other manual, and any manual that documents TypoScript options of its own.
 *
 * Options are filled after parsing, from every page, before the roles are
 * described. @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CollectTypoScriptOptionsTransformer
 * Object types are looked up among the manual's own anchors.
 *
 * The link is a permalink, as for a ViewHelper: an address that does not
 * depend on the page the role is on. A manual without permalinks gives no
 * link, only the description.
 *
 * @phpstan-import-type Option from ExternalTypoScript
 * @phpstan-import-type ObjectType from ObjectTypes
 */
final class LocalTypoScript
{
    private Options $options;

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly Permalinks $permalinks,
    ) {
        $this->options = new Options();
    }

    /**
     * @param Option $option
     * @param list<string> $paths the paths the option declares
     * @param bool $typoScript whether a role finds it by itself, or only by its name
     */
    public function addOption(string $anchor, array $option, array $paths, bool $typoScript = true): void
    {
        $this->options->add($anchor, $option, $paths, $typoScript);
    }

    /** @return Option|null any option of the manual, by the name of its confval */
    public function findNamed(string $name): ?array
    {
        return $this->options->named($name, $this->anchorNormalizer);
    }

    /** @return Option|null */
    public function findOption(string $path): ?array
    {
        return $this->options->find($path, $this->anchorNormalizer);
    }

    /** @return Option|null */
    public function findWord(string $word): ?array
    {
        return $this->options->findWord($word, $this->anchorNormalizer);
    }

    /** @return ObjectType|null */
    public function findObjectType(string $type, ProjectNode $projectNode): ?array
    {
        return ObjectTypes::find(
            $type,
            $this->anchorNormalizer->reduceAnchor($type),
            function (string $anchor) use ($projectNode): ?array {
                $target = $projectNode->getInternalTarget($anchor, SectionNode::STD_LABEL);
                if ($target === null) {
                    return null;
                }

                return [
                    'title' => $target->getTitle() ?? '',
                    'url' => $this->permalinks->forAnchor($anchor, $projectNode->getVersion()),
                ];
            },
            // A page has no permalink of its own, only its anchors do.
            [],
        );
    }
}
