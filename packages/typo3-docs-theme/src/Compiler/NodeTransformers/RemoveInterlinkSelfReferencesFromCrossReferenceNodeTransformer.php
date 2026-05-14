<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function assert;

/** @implements NodeTransformer<CrossReferenceNode> */
final class RemoveInterlinkSelfReferencesFromCrossReferenceNodeTransformer implements NodeTransformer
{
    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        assert($node instanceof CrossReferenceNode);
        if (!$this->themeSettings->hasSettings('interlink_shortcode')) {
            return $node;
        }
        $interlink = $this->themeSettings->getSettings('interlink_shortcode');
        if ($interlink === '' || $node->getInterlinkDomain() !== $interlink) {
            return $node;
        }
        // Remove interlink references to the own current document
        if ($node instanceof ReferenceNode) {
            $newRef = new ReferenceNode(
                $node->getTargetReference(),
                $node->getValue(),
                '',
                $node->getLinkType(),
                $node->getPrefix()
            );
            return $newRef;
        }
        if ($node instanceof DocReferenceNode) {
            $newDocRef = new DocReferenceNode(
                $node->getTargetReference(),
                $node->getValue(),
            );
            return $newDocRef;
        }
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CrossReferenceNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
