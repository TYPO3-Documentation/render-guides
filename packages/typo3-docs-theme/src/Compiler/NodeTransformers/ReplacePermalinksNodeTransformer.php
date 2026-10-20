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
use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function assert;
use function explode;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;

/**
 * Turns a "docs.typo3.org/permalink/…" URL into the reference it stands for,
 * and reports the URL when the published route would not have resolved it.
 *
 * Rendering resolves a permalink against the inventories held in memory, which
 * accepts more than the "/permalink/" route on docs.typo3.org does. Two forms
 * therefore render without a warning and answer 404 for every reader -- and
 * they stay in the source, where permalinks are meant to be opened from. See
 * issue #1402; 110 such links were found across the official manuals, every one
 * green in CI indefinitely.
 *
 * The rules are taken from the route itself, TYPO3GmbH/site-intercept,
 * "legacy_hook/src/DocumentationLinker.php", rather than guessed from probing.
 *
 * The rendered link is not affected: it is a resolved deep link and works. What
 * is wrong is the URL an author wrote, which is why this warns rather than
 * repairs.
 *
 * @implements NodeTransformer<HyperLinkNode|ReferenceNode>
 */
final class ReplacePermalinksNodeTransformer implements NodeTransformer
{
    private const PERMALINK_PREFIX = 'https://docs.typo3.org/permalink/';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        assert($node instanceof HyperLinkNode);
        $permalink = $node->getTargetReference();
        // A link to a named link, such as "`ModifyRenderedRecordEvent`_",
        // targets the name. When the named link is a permalink, the link
        // goes where that permalink goes. Its definition was reported already.
        $named = !str_starts_with($permalink, self::PERMALINK_PREFIX);
        if ($named) {
            $permalink = $compilerContext->getDocumentNode()->getLink($permalink) ?? '';
            if (!str_starts_with($permalink, self::PERMALINK_PREFIX)) {
                return $node;
            }
        }

        $value = $node->getValue();
        if ($value === $permalink) {
            $value = '';
        }

        $url = str_replace(self::PERMALINK_PREFIX, '', $permalink);
        $version = null;
        $interlink = null;
        if (str_contains($url, '@')) {
            [$url, $version] = explode('@', $url, 2);
        }

        if (str_contains($url, ':')) {
            [$interlink, $url] = explode(':', $url, 2);
        }

        if (!$named) {
            $this->reportUnresolvableByTheRoute($permalink, $interlink, $url, $compilerContext);
        }

        if ($version !== null && $interlink !== null) {
            $interlink = $interlink . '/' . $version;
        }

        return new ReferenceNode($url, $value, $interlink ?? '');
    }

    /**
     * The forms the route rejects although rendering accepts them.
     *
     * Reported rather than corrected: the rendered page already links to a
     * resolved deep link that works, so there is nothing to repair there. The
     * broken URL is the one in the source file, and only its author can fix it.
     */
    private function reportUnresolvableByTheRoute(
        string $permalink,
        string|null $interlink,
        string $anchor,
        CompilerContextInterface $compilerContext,
    ): void {
        // A file path where the anchor belongs, as the link modal offered for a
        // headline without an anchor: "manual:Path/Index#section". The route
        // accepts only letters, digits, "-" and "_" there, so no spelling of
        // the path resolves -- normalising it would suggest a second URL that
        // answers 404. What resolves is an anchor on the headline. Without a
        // manual, a slash may belong to a manual key such as "georgringer/news"
        // instead, so only the "#" tells a path there.
        if (str_contains($anchor, '#') || ($interlink !== null && str_contains($anchor, '/'))) {
            $this->logger->warning(
                sprintf(
                    'The permalink "%s" names the file "%s" instead of an anchor and resolves to 404. '
                    . 'Link to the anchor of the headline. If the headline has none, add one.',
                    $permalink,
                    explode('#', $anchor, 2)[0],
                ),
                $compilerContext->getLoggerInformation(),
            );

            return;
        }

        // The published inventory holds anchors normalised, the in-memory one
        // holds them as written, so "run_upgrade_wizard" resolves here and 404s
        // there.
        //
        // Compared lowercased, because the route lowercases before it looks up
        // ($index = mb_strtolower($index)): "typo3ConfVars" resolves as written
        // and must not be reported. What it does not do is turn an underscore
        // into a hyphen, which is the case that breaks.
        $normalized = $this->anchorNormalizer->reduceAnchor($anchor);
        $asTheRouteReadsIt = strtolower($anchor);

        // No manual. The route's pattern requires "repository:index", so a
        // permalink without a colon never matches and answers 404; rendering
        // resolves it against the manual being built, which is why it passes
        // here. The replacement carries the normalised anchor, or it would be a
        // second URL that answers 404.
        if ($interlink === null) {
            $own = $this->themeSettings->getSettings('interlink_shortcode');

            $this->logger->warning(
                sprintf(
                    'The permalink "%s" names no manual and resolves to 404. Write "%s:%s".',
                    $permalink,
                    $own !== '' ? $own : 'manual',
                    $normalized,
                ),
                $compilerContext->getLoggerInformation(),
            );

            return;
        }

        // A slashed manual key such as "web-vision/deepltranslate-core" is not
        // reported: the route uses it as written since
        // TYPO3GmbH/site-intercept#321, and it is the form that resolves for
        // every vendor. The dash form does not, "web-vision-deepltranslate-core"
        // is looked up as "/p/web/vision-deepltranslate-core/".
        if ($anchor !== '' && $asTheRouteReadsIt !== $normalized) {
            $this->logger->warning(
                sprintf(
                    'The permalink "%s" uses the anchor "%s", which is published as "%s".',
                    $permalink,
                    $anchor,
                    $normalized,
                ),
                $compilerContext->getLoggerInformation(),
            );
        }
    }

    public function supports(Node $node): bool
    {
        return $node instanceof HyperLinkNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
