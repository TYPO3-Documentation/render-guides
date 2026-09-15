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
use T3Docs\VersionHandling\DefaultInventories;

use function assert;
use function sprintf;
use function preg_match;
use function preg_replace;
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
        if (!str_starts_with($node->getTargetReference(), self::PERMALINK_PREFIX)) {
            return $node;
        }

        $value = $node->getValue();
        if ($value === $node->getTargetReference()) {
            $value = '';
        }

        $url = str_replace(self::PERMALINK_PREFIX, '', $node->getTargetReference());
        $version = null;
        $interlink = null;
        if (str_contains($url, '@')) {
            [$url, $version] = explode('@', $url, 2);
        }

        if (str_contains($url, ':')) {
            [$interlink, $url] = explode(':', $url, 2);
        }

        $this->reportUnresolvableByTheRoute(
            $node->getTargetReference(),
            $interlink,
            $url,
            $compilerContext,
        );

        if ($version !== null && $interlink !== null) {
            $interlink = $interlink . '/' . $version;
        }

        return new ReferenceNode($url, $value, $interlink ?? '');
    }

    /**
     * Two forms the route rejects although rendering accepts them.
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
                    $own !== '' ? $this->routeKey($own) : 'manual',
                    $normalized,
                ),
                $compilerContext->getLoggerInformation(),
            );

            return;
        }

        if ($this->routeMisreadsRepository($interlink)) {
            $this->logger->warning(
                sprintf(
                    'The permalink "%s" uses the interlink key "%s", which the route reads as the manual "%s". Write "%s".',
                    $permalink,
                    $interlink,
                    preg_replace('/-/', '/', strtolower($interlink), 1),
                    $this->routeKey($interlink),
                ),
                $compilerContext->getLoggerInformation(),
            );
        }

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

    /**
     * Whether the route would look the manual up somewhere else than intended.
     *
     * For a third-party manual it builds the path by replacing the *first*
     * hyphen with a slash: "friendsoftypo3-content-blocks" becomes
     * "/p/friendsoftypo3/content-blocks/". Hand it the interlink key
     * "friendsoftypo3/content-blocks" instead and the first hyphen is the one
     * inside the package name, so it looks for "/p/friendsoftypo3/content/blocks/"
     * and answers 404.
     *
     * Two slashed keys survive this and are not reported. A "typo3/…" key is
     * matched before that branch and maps to "/c/typo3/…", and a key with no
     * hyphen at all -- "georgringer/news" -- has nothing to replace. Both
     * resolve today, so warning about them would be noise.
     */
    private function routeMisreadsRepository(string $interlink): bool
    {
        if (!str_contains($interlink, '/') || !str_contains($interlink, '-')) {
            return false;
        }

        if (preg_match('/^typo3[\/-]/i', $interlink) === 1) {
            return false;
        }

        return DefaultInventories::tryFrom(strtolower($interlink)) === null;
    }

    /** The manual key as the "/permalink/" route spells it. */
    private function routeKey(string $interlink): string
    {
        return str_replace('/', '-', $interlink);
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
