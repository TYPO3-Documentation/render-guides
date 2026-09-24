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
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\CheckHeadlineAnchorsNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function in_array;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Warns about a headline without an anchor of its own.
 *
 * Only a label written before a headline registers an anchor that a permalink
 * resolves; the id derived from the title works on the page alone and changes
 * with the wording. A manual opts in with check-headline-anchors="true"; a
 * page field ":check-headline-anchors:" overrides that for its page, either
 * way. The page title counts as a headline too.
 *
 * Duplicate anchors are reported already, by phpDocumentor/guides itself.
 *
 * @implements NodeTransformer<SectionNode>
 */
final class CheckHeadlineAnchorsNodeTransformer implements NodeTransformer
{
    private const OFF = ['', 'false', '0', 'off', 'no'];

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly SettingsManager $settingsManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof SectionNode || !$this->enabledFor($compilerContext->getDocumentNode())) {
            return $node;
        }

        foreach ($node->getChildren() as $child) {
            if ($child instanceof AnchorNode) {
                return $node;
            }
        }

        $this->logger->warning(sprintf(
            'The headline "%s" has no anchor, so no permalink leads to it. Give it one: ..  _%s:',
            $node->getTitle()->toString(),
            $node->getId(),
        ), $compilerContext->getLoggerInformation());

        return $node;
    }

    private function enabledFor(DocumentNode $document): bool
    {
        // A Markdown headline cannot carry a label, so there is nothing an
        // author could do about the warning.
        if ($this->settingsManager->getProjectSettings()->getInputFormat() === 'md') {
            return false;
        }

        $value = $this->themeSettings->getSettings('check_headline_anchors');
        foreach ($document->getHeaderNodes() as $headerNode) {
            if ($headerNode instanceof CheckHeadlineAnchorsNode) {
                $value = $headerNode->toString();
            }
        }

        return !in_array(strtolower(trim($value)), self::OFF, true);
    }

    public function supports(Node $node): bool
    {
        return $node instanceof SectionNode;
    }

    public function getPriority(): int
    {
        // Well after MoveAnchorTransformer (30000) has put each label into the
        // section it stands before.
        return 800;
    }
}
