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
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Directives\AbstractTypo3VersionChangeDirective;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\CheckLinkTextNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function in_array;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Warns about a reference the author gave no link text of its own.
 *
 * Such a reference shows the title of its target, which is filled in when the
 * page is rendered -- by then the missing text can no longer be told apart,
 * so the check runs here. A manual opts in with check-link-text="true"; a
 * page field ":check-link-text:" overrides that for its page, either way.
 *
 * @implements NodeTransformer<CrossReferenceNode>
 */
final class CheckLinkTextNodeTransformer implements NodeTransformer
{
    private const OFF = ['', 'false', '0', 'off', 'no'];

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$this->enabledFor($compilerContext->getDocumentNode())) {
            return $node;
        }
        if (!$node instanceof AbstractLinkInlineNode || $node->getChildren() !== [] || !$this->isWrittenByAuthor($node)) {
            return $node;
        }
        $role = $node instanceof DocReferenceNode ? 'doc' : 'ref';
        $target = $node->getTargetReference();
        if ($node instanceof CrossReferenceNode && $node->getInterlinkDomain() !== '') {
            $target = $node->getInterlinkDomain() . ':' . $target;
        }
        $this->logger->warning(sprintf(
            'The reference to "%s" has no link text, so it shows the title of its target. Give it its own: :%s:`Link text <%s>`.',
            $target,
            $role,
            $target,
        ), $compilerContext->getLoggerInformation());

        return $node;
    }

    private function enabledFor(DocumentNode $document): bool
    {
        $value = $this->themeSettings->getSettings('check_link_text');
        foreach ($document->getHeaderNodes() as $headerNode) {
            if ($headerNode instanceof CheckLinkTextNode) {
                $value = $headerNode->toString();
            }
        }

        return !in_array(strtolower(trim($value)), self::OFF, true);
    }

    /**
     * A :doc: or :ref: the author wrote, or a permalink URL, which becomes a
     * :ref: before this runs. Everything else without children means it:
     * :php:, :confval: and their kind show the name they point to, and the
     * :changelog: option of versionchanged shows the entry's title by design.
     */
    private function isWrittenByAuthor(Node $node): bool
    {
        if ($node instanceof DocReferenceNode) {
            return true;
        }

        return $node instanceof ReferenceNode
            && $node->getLinkType() === SectionNode::STD_LABEL
            && !in_array(AbstractTypo3VersionChangeDirective::CHANGELOG_LINK_CLASS, $node->getClasses(), true);
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CrossReferenceNode;
    }

    public function getPriority(): int
    {
        // After ReplacePermalinksNodeTransformer (1000) turns a permalink into
        // a :ref:, and after RedirectsNodeTransformer (900) rebuilds one.
        return 800;
    }
}
