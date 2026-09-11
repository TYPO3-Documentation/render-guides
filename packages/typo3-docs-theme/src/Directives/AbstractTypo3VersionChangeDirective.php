<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkParser;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_values;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;
use function trim;

/**
 * TYPO3 specific version of phpDocumentor's version change directives
 * (versionadded, versionchanged, deprecated).
 *
 * All three directives support a ":changelog:" option that renders a link to
 * the related changelog entry. The value is resolved as a cross-reference -
 * against the core changelog inventory, against another manual's inventory, or
 * against this manual's own labels, depending on the form - so a target that
 * does not exist produces a warning and the theme's unresolved-reference
 * marker, not a link that 404s when clicked.
 *
 * For a TYPO3 core change, pass the changelog entry identifier; it is resolved
 * against the "changelog" inventory:
 *
 * ..  versionchanged:: 14.0
 *     :changelog: feature-107628-1729026000
 *
 *     This module has been moved from :guilabel:`System` to
 *     :guilabel:`Administration`.
 *
 * For an extension change, pass the extension's interlink shortcode
 * ("vendor/package") followed by the changelog entry anchor. It is resolved
 * against that extension's inventory:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: acme/acme-blog:changes-2-0-0
 *
 *     The teaser field was renamed; see the changelog entry for the migration.
 *
 * When linking the changelog of the current manual itself, use the short
 * "#anchor" form. It resolves against this manual's own labels:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: #changes-2-0-0
 *
 *     A configuration option was renamed; see the changelog for the migration.
 */
abstract class AbstractTypo3VersionChangeDirective extends SubDirective
{
    private const CHANGELOG_INVENTORY = 'changelog';
    private const CHANGELOG_LINK_CLASS = 'versionchange-changelog';

    /** Shown as the link text, and as the reference's value in a warning when the entry is missing. */
    private const CHANGELOG_LINK_TEXT = 'See changelog entry';

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        Rule $startingRule,
        private readonly string $type,
        private readonly string $label,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
        private readonly InterlinkParser $interlinkParser,
    ) {
        parent::__construct($startingRule);
    }

    final public function getName(): string
    {
        return $this->type;
    }

    final protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node {
        return new Typo3VersionChangeNode(
            $this->type,
            $this->label,
            $directive->getData(),
            array_values($collectionNode->getChildren()),
            $this->buildChangelogReference($directive, $blockContext),
        );
    }

    /**
     * Turn the ":changelog:" option into a reference that is resolved during
     * rendering: against the core changelog inventory, against another manual's
     * inventory, or against this manual's own labels. Returns null when the
     * option is absent, valueless or malformed (a warning is logged in the
     * latter two cases).
     */
    private function buildChangelogReference(Directive $directive, BlockContext $blockContext): ReferenceNode|null
    {
        if (!$directive->hasOption('changelog')) {
            return null;
        }

        // ":changelog:" with nothing behind it is parsed as the flag "true", which
        // strval()s to "1" and would otherwise be taken for a changelog entry id.
        $value = $directive->getOption('changelog')->getValue();
        $changelog = $value === true ? '' : trim((string) $value);

        if ($changelog === '') {
            $this->logger->warning(
                'The ":changelog:" option was given without a changelog entry. ',
                $blockContext->getLoggerInformation(),
            );

            return null;
        }

        // Every form of the value is a single token. Whitespace means the value is not one -
        // most often because it was written on the following line, which the parser appends
        // to the flag the empty option produced, yielding a leading "1".
        if (preg_match('/\s/', $changelog) === 1) {
            $this->logger->warning(
                sprintf(
                    'The ":changelog: %s" option contains whitespace; the changelog entry must be a single token on the same line as the option. ',
                    $changelog,
                ),
                $blockContext->getLoggerInformation(),
            );

            return null;
        }

        $ownShortcode = $this->themeSettings->getSettings('interlink_shortcode');

        if (str_starts_with($changelog, '#')) {
            // "#anchor": the changelog of the current manual itself. Emit a local
            // reference (empty interlink domain) so it resolves against this
            // manual's own labels.
            $interlinkDomain = '';
            $anchor = trim(substr($changelog, 1));
        } else {
            // "<shortcode>:<anchor>" is the same syntax every other cross-reference uses, so the
            // canonical parser splits it. Note that the "vendor/package" form only parses because
            // this package rebinds InterlinkParser to ExtendedInterlinkParser, whose domain
            // pattern allows "/"; upstream's DefaultInterlinkParser does not. It returns an empty interlink for anything that is not a
            // valid "<domain>:", which is either the bare form or a malformed one.
            $interlink = $this->interlinkParser->extractInterlink($changelog);

            if ($interlink->interlink !== '') {
                $interlinkDomain = $interlink->interlink;
                $anchor = trim($interlink->reference);

                // A reference to the manual's own shortcode is a self-reference;
                // emit it as a local reference, like the "#anchor" form.
                if ($interlinkDomain === $ownShortcode) {
                    $interlinkDomain = '';
                }
            } elseif (str_contains($changelog, ':')) {
                // A colon the parser would not accept as a domain separator: the shortcode is
                // missing or carries characters an interlink domain cannot have.
                $this->logger->warning(
                    sprintf('The ":changelog: %s" option is malformed (no usable shortcode before the colon). ', $changelog),
                    $blockContext->getLoggerInformation(),
                );

                return null;
            } else {
                // Bare value: a TYPO3 core changelog entry identifier.
                $interlinkDomain = self::CHANGELOG_INVENTORY;
                $anchor = $changelog;
            }
        }

        if ($anchor === '') {
            $this->logger->warning(
                sprintf('The ":changelog: %s" option has an empty changelog entry anchor. ', $changelog),
                $blockContext->getLoggerInformation(),
            );

            return null;
        }

        $reference = new ReferenceNode(
            $anchor,
            [new PlainTextInlineNode(self::CHANGELOG_LINK_TEXT)],
            $interlinkDomain,
        );
        // The template renders the node as-is, so the class has to travel with it.
        $reference->setClasses([self::CHANGELOG_LINK_CLASS]);

        return $reference;
    }
}
