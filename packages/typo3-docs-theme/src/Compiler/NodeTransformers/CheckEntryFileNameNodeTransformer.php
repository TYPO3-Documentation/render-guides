<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * Warns when a manual starts at "index.rst" or "index.md" rather than at
 * "Index.rst" or "Index.md".
 *
 * Both render, since phpDocumentor/guides accepts either spelling, and a local
 * render looks fine. But the page is written to "index.html", and
 * docs.typo3.org opens a manual at "Index.html": deployed, the manual has no
 * start page, and its address leads to a 404.
 *
 * Only the entry file: a lower-case "index" further down is an ordinary page
 * that the table of contents links to by its name.
 *
 * guides picks the entry file from the directory listing, not by asking the
 * filesystem whether a name exists, so a root document named "index" is one
 * even on a filesystem that ignores case.
 *
 * @implements NodeTransformer<DocumentNode>
 */
final class CheckEntryFileNameNodeTransformer implements NodeTransformer
{
    private const LOWER_CASE_ENTRY = 'index';
    private const EXPECTED_ENTRY = 'Index';

    private bool $disabled = false;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * For the integration fixtures, which are never deployed: most of them
     * start at "index.rst", and renaming them all is a case-only rename that
     * git handles badly on macOS and Windows.
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($this->disabled || !$node instanceof DocumentNode || !$node->isRoot() || $node->getFilePath() !== self::LOWER_CASE_ENTRY) {
            return $node;
        }

        $extension = $this->settingsManager->getProjectSettings()->getInputFormat();
        $this->logger->warning(sprintf(
            'The manual starts at "%1$s.%3$s". docs.typo3.org opens a manual at "%2$s.html", so once deployed it has no start page. Rename the file to "%2$s.%3$s".',
            self::LOWER_CASE_ENTRY,
            self::EXPECTED_ENTRY,
            $extension,
        ), $compilerContext->getLoggerInformation());

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof DocumentNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
