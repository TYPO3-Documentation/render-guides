<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function dirname;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;

/**
 * Warns when a manual declares no "interlink-shortcode" in its guides.xml.
 *
 * The shortcode is how a manual is known: its permalinks are built on it, and
 * other manuals link to it by it. Without one the manual has no permalinks,
 * and the "Reference this headline" popup offers an rST reference to
 * "somemanual", which leads nowhere.
 *
 * For an extension the shortcode is its Composer name. When a composer.json
 * beside the documentation names the package, the warning quotes the exact
 * attribute to add.
 *
 * A localization is rendered on its own, from a guides.xml of its own, and its
 * permalinks are those of the manual it translates: it is not checked.
 *
 * @implements NodeTransformer<DocumentNode>
 */
final class CheckInterlinkShortcodeNodeTransformer implements NodeTransformer
{
    private const DOCUMENTATION = 'https://docs.typo3.org/permalink/h2document:guides-extension-interlink-shortcode';

    /** @see https://getcomposer.org/doc/04-schema.md#name */
    private const COMPOSER_NAME = '/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/';

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
            || $this->themeSettings->getSettings('interlink_shortcode') !== ''
            || $this->inputSettings->getInput()?->hasParameterOption('--localization') === true
        ) {
            return $node;
        }

        $composerName = $this->composerName();
        $this->logger->warning(sprintf(
            'The manual declares no interlink shortcode, so it has no permalinks and other manuals cannot link to it. %s See %s',
            $composerName === null
                ? 'Add interlink-shortcode="..." to the <extension> element of guides.xml; for an extension it is the Composer name.'
                : sprintf('Add interlink-shortcode="%s" to the <extension> element of guides.xml.', $composerName),
            self::DOCUMENTATION,
        ), $compilerContext->getLoggerInformation());

        return $node;
    }

    /**
     * The package name of a composer.json in the rendered directory or the
     * one above it -- where it sits beside "Documentation/".
     */
    private function composerName(): ?string
    {
        $input = $this->settingsManager->getProjectSettings()->getInput();
        foreach ([$input, dirname($input)] as $directory) {
            $file = $directory . '/composer.json';
            if (!is_file($file)) {
                continue;
            }

            $composer = json_decode((string) file_get_contents($file), true);
            $name = is_array($composer) ? ($composer['name'] ?? null) : null;

            return is_string($name) && preg_match(self::COMPOSER_NAME, $name) === 1 ? $name : null;
        }

        return null;
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
