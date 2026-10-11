<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Settings\ComposerPackage;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function sprintf;

/**
 * Warns when a manual declares no "interlink-shortcode" in its guides.xml, or
 * a third-party extension declares one other than its Composer name.
 *
 * The shortcode is how a manual is known: its permalinks are built on it, and
 * other manuals link to it by it. Without one the manual has no permalinks,
 * and the "Reference this headline" popup offers an rST reference to
 * "somemanual", which leads nowhere.
 *
 * An extension without one uses its Composer name, see
 * InterlinkShortcodeFromComposer, and is not warned about.
 *
 * docs.typo3.org resolves the permalinks of a third-party extension by its
 * Composer name only: "news:start" answers 404, "georgringer/news:start" does
 * not. A system extension is not checked; the Core changelog is known as
 * "changelog".
 *
 * A localization is rendered on its own, from a guides.xml of its own, and its
 * permalinks are those of the manual it translates: it is not checked.
 *
 * @implements NodeTransformer<DocumentNode>
 */
final class CheckInterlinkShortcodeNodeTransformer implements NodeTransformer
{
    private const DOCUMENTATION = 'https://docs.typo3.org/permalink/h2document:guides-extension-interlink-shortcode';

    private bool $disabled = false;

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly SettingsManager $settingsManager,
        private readonly Typo3DocsInputSettings $inputSettings,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * For the integration fixtures, most of which render without a
     * guides.xml, let alone a shortcode.
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (
            $this->disabled
            || !$node instanceof DocumentNode
            || !$node->isRoot()
            || $this->inputSettings->getInput()?->hasParameterOption('--localization') === true
        ) {
            return $node;
        }

        $shortcode = $this->themeSettings->getSettings('interlink_shortcode');
        if ($shortcode !== '') {
            $package = ComposerPackage::findFor($this->settingsManager->getProjectSettings()->getInput());
            if ($package?->isThirdPartyExtension() === true && $shortcode !== $package->name) {
                $this->logger->warning(sprintf(
                    'The interlink shortcode "%s" is not the Composer name of the extension, so its permalinks do not resolve on docs.typo3.org. Use interlink-shortcode="%s". See %s',
                    $shortcode,
                    $package->name,
                    self::DOCUMENTATION,
                ), $compilerContext->getLoggerInformation());
            }

            return $node;
        }

        $this->logger->warning(sprintf(
            'The manual declares no interlink shortcode, so it has no permalinks and other manuals cannot link to it. Add interlink-shortcode="..." to the <extension> element of guides.xml; for an extension it is the Composer name. See %s',
            self::DOCUMENTATION,
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
