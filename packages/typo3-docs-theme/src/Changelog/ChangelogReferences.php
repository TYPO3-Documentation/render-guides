<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Changelog;

use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkParser;
use phpDocumentor\Guides\RestructuredText\Parser\References\EmbeddedReferenceParser;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Directives\AbstractTypo3VersionChangeDirective;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;
use function trim;

/**
 * A link to a changelog entry, from the value an author writes for it.
 *
 * The value names the entry: bare, it is an entry of the TYPO3 Core
 * changelog; "#anchor" is one of the manual itself; "<shortcode>:<anchor>" one
 * of another manual; and "Link text <entry>" gives the link a text of its
 * own. The reference is resolved during rendering, which warns about an entry
 * that does not exist.
 *
 * The version directives take the value from their ":changelog:" option, a
 * confval from the options that say when it was added, changed, deprecated or
 * removed. @see AbstractTypo3VersionChangeDirective
 */
final class ChangelogReferences
{
    use EmbeddedReferenceParser;

    private const CHANGELOG_INVENTORY = 'changelog';

    /**
     * Unicode-aware, because the ASCII class misses the separators an editor inserts by
     * accident - an ideographic space or a zero-width space would otherwise travel into
     * the reference and render an unresolvable target nobody can see.
     */
    private const WHITESPACE = '/[\s\p{Z}\p{Cf}]/u';

    /** The same class, anchored: a value the parser continued onto this line begins with it. */
    private const LEADING_WHITESPACE = '/^[\s\p{Z}\p{Cf}]/u';

    /** An empty pattern with /u matches any valid UTF-8 subject and fails on a malformed one. */
    private const VALID_UTF8 = '//u';
    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
        private readonly InterlinkParser $interlinkParser,
    ) {}

    /**
     * The reference, or null for a value that names no usable entry, which is
     * reported under the name of the option it came from.
     *
     * @param array<string, mixed> $loggerInformation
     */
    public function build(string $changelog, string $option, array $loggerInformation): ReferenceNode|null
    {
        // Read untrimmed on purpose. The parser trims a value written on the directive's own
        // line and prepends a space to one written on a following line, so leading whitespace
        // is the only thing telling the two apart; trimming first would let an invisible
        // trailing space after the option name decide whether a link is rendered at all.
        // Checked before the whitespace patterns below, which return false rather than 0 on a
        // malformed subject: "=== 1" would then read that as "no whitespace", and the value
        // would travel on to the anchor normalizer, where UnicodeString throws and takes the
        // whole render down - every page lost, and the message naming a template rather than
        // the file the author has to fix. Invalid UTF-8 is that constructor's only throw
        // condition, so excluding it here is what makes the rest of this method total. The
        // value is kept out of the message, it is the one thing that cannot be logged as is.
        if (preg_match(self::VALID_UTF8, $changelog) !== 1) {
            $this->logger->warning(
                sprintf('The ":%s:" option value is not valid UTF-8. ', $option),
                $loggerInformation,
            );

            return null;
        }

        // Since the value may carry a link text, whitespace inside it is no longer a rejection
        // on its own, and the leading-whitespace case has to be named before the text is split
        // off: written with a trailing space after the option name and the value on the following
        // line, that space is all that distinguishes the continued form from an inline one.
        if (preg_match(self::LEADING_WHITESPACE, $changelog) === 1) {
            $this->logger->warning(
                sprintf(
                    'The ":%s: %s" option value is continued from the option line; a changelog entry stands on the same line as the option. ',
                    $option,
                    trim($changelog),
                ),
                $loggerInformation,
            );

            return null;
        }

        if ($changelog === '') {
            $this->logger->warning(
                sprintf('The ":%s:" option was given without a changelog entry. ', $option),
                $loggerInformation,
            );

            return null;
        }

        // "Link text <entry>" is the embedded form every reference in the project accepts, so
        // the canonical parser splits it rather than a pattern of its own. A value
        // without the brackets is the entry itself and carries no text, which is the usual case:
        // the link then reads as the title of the entry it resolves to.
        $embedded = $this->extractEmbeddedReference($changelog);
        $linkText = $embedded->text;
        $entry = $embedded->reference;

        // The entry is still a single token - a text the author supplies is the only part of
        // the value allowed to carry whitespace. This also rejects the other continued form,
        // where the valueless option's "1" arrives in front of the entry.
        if (preg_match(self::WHITESPACE, $entry) === 1) {
            $this->logger->warning(
                sprintf(
                    'The ":%s: %s" changelog entry is not a single token; an entry carries no whitespace. ',
                    $option,
                    trim($changelog),
                ),
                $loggerInformation,
            );

            return null;
        }

        // No guard for an empty entry here: the value is known to be non-empty and to carry no
        // leading whitespace, and the embedded parser only ever returns an empty reference for a
        // value that is empty or all whitespace. An entry that is empty after the "#" or the
        // shortcode is a different case, caught below once the form is known.
        $ownShortcode = $this->themeSettings->getSettings('interlink_shortcode');

        if (str_starts_with($entry, '#')) {
            // "#anchor": the changelog of the current manual itself. Emit a local
            // reference (empty interlink domain) so it resolves against this
            // manual's own labels.
            $interlinkDomain = '';
            $anchor = trim(substr($entry, 1));
        } else {
            // "<shortcode>:<anchor>" is the same syntax every other cross-reference uses, so the
            // canonical parser splits it. Note that the "vendor/package" form only parses because
            // this package rebinds InterlinkParser to ExtendedInterlinkParser, whose domain
            // pattern allows "/"; upstream's DefaultInterlinkParser does not. It returns an empty interlink for anything that is not a
            // valid "<domain>:", which is either the bare form or a malformed one.
            $interlink = $this->interlinkParser->extractInterlink($entry);

            if ($interlink->interlink !== '') {
                $interlinkDomain = $interlink->interlink;
                $anchor = trim($interlink->reference);

                // A reference to the manual's own shortcode is a self-reference;
                // emit it as a local reference, like the "#anchor" form.
                if ($interlinkDomain === $ownShortcode) {
                    $interlinkDomain = '';
                }
            } elseif (str_contains($entry, ':')) {
                // A colon the parser would not accept as a domain separator: the shortcode is
                // missing or carries characters an interlink domain cannot have.
                $this->logger->warning(
                    sprintf('The ":%s: %s" option is malformed (no usable shortcode before the colon). ', $option, $changelog),
                    $loggerInformation,
                );

                return null;
            } else {
                // Bare value: a TYPO3 core changelog entry identifier.
                $interlinkDomain = self::CHANGELOG_INVENTORY;
                $anchor = $entry;
            }
        }

        if ($anchor === '') {
            $this->logger->warning(
                sprintf('The ":%s: %s" option has an empty changelog entry anchor. ', $option, $changelog),
                $loggerInformation,
            );

            return null;
        }

        // Without children the resolver fills the reference with the title of the entry it
        // resolves to, which is what makes each link say where it leads; an author-supplied
        // text takes that place, as plain text, exactly as the embedded form does elsewhere.
        $reference = new ReferenceNode(
            $anchor,
            $linkText === null || $linkText === '' ? [] : [new PlainTextInlineNode($linkText)],
            $interlinkDomain,
        );
        // The template renders the node as-is, so the class has to travel with it.
        $reference->setClasses([AbstractTypo3VersionChangeDirective::CHANGELOG_LINK_CLASS]);

        return $reference;
    }
}
