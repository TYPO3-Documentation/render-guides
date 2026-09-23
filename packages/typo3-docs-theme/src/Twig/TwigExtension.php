<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use League\Flysystem\FilesystemException;
use LogicException;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Metadata\NoSearchNode;
use phpDocumentor\Guides\Nodes\Metadata\OrphanNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use T3Docs\GuidesPhpDomain\Nodes\PhpComponentNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpMemberNode;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogEntry;
use T3Docs\Typo3DocsTheme\Deployment\DeploymentMode;
use T3Docs\Typo3DocsTheme\Directives\SiteSetSettingsDirective;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\EditOnGitHubNode;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\TemplateNode;
use T3Docs\Typo3DocsTheme\Nodes\PageLinkNode;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperArgumentNode;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperNode;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\Typo3DocsThemeMd\Anchors\AddressableAnchors;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    /**
     * @see https://regex101.com/r/qWKenb/1
     */
    public const CAMEL_CASE_BREAK_REGEX = '/([a-z])([A-Z])/';

    /**
     * @see https://regex101.com/r/nxExYM/2
     */
    public const NON_LETTER_BREAK_REGEX = '/(?<!^)([.\_\-\\\\\/:])([a-zA-Z0-9])/';

    /**
     * @see https://regex101.com/r/uIul8d/1
     */
    public const BRACKETS_BREAK_REGEX = '/(?<!^)([\[\(\{\<\|])/';

    /**
     * Where a Forge issue lives. Spelled out as data rather than left for the
     * reader to assemble from "issue".
     */
    private const FORGE_ISSUE_URL = 'https://forge.typo3.org/issues/';

    private string $typo3AzureEdgeURI = '';

    /**
     * Documents already reported for a missing "interlink-shortcode", so the
     * configuration problem is stated once and not once per link.
     *
     * @var array<string, true>
     */
    private array $reportedMissingShortcode = [];

    /**
     * Documents already reported for an unusable project version.
     *
     * @var array<string, true>
     */
    private array $reportedMissingVersion = [];

    public function __construct(
        private readonly LoggerInterface               $logger,
        private readonly UrlGeneratorInterface         $urlGenerator,
        private readonly Typo3DocsThemeSettings        $themeSettings,
        private readonly DocumentNameResolverInterface $documentNameResolver,
        private readonly Typo3VersionService           $typo3VersionService,
        private readonly AnchorNormalizer              $anchorNormalizer,
        private readonly ChangelogEntry                $changelogEntry,
        private readonly Permalinks                    $permalinks,
        private readonly DeploymentMode                $deploymentMode,
    ) {
        $this->typo3AzureEdgeURI = $deploymentMode->azureEdgeUri();
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderPlainText', $this->renderPlainText(...), ['needs_context' => false]),
            new TwigFunction('getAnchorIdOfSection', $this->getAnchorIdOfSection(...), ['needs_context' => true]),
            new TwigFunction('getEditOnGitHubLink', $this->getEditOnGitHubLink(...), ['needs_context' => true]),
            new TwigFunction('getEditOnGitHubLinkFromPath', $this->getEditOnGitHubLinkFromPath(...), ['needs_context' => true]),
            new TwigFunction('getReportIssueLink', $this->getReportIssueLink(...), ['needs_context' => true]),
            new TwigFunction('getCurrentFilename', $this->getCurrentFilename(...), ['needs_context' => true]),
            new TwigFunction('sourceFilename', $this->getSourceFilename(...), ['needs_context' => true]),
            new TwigFunction('markdownAlternate', $this->getMarkdownAlternate(...), ['needs_context' => true]),
            new TwigFunction('markdownDownloadName', $this->getMarkdownDownloadName(...), ['needs_context' => true]),
            new TwigFunction('markdownLinkUrl', $this->getMarkdownLinkUrl(...), ['needs_context' => true]),
            new TwigFunction('markdownSectionAnchors', $this->getMarkdownSectionAnchors(...), ['needs_context' => true]),
            new TwigFunction('headingId', $this->getHeadingId(...), ['needs_context' => true]),
            new TwigFunction('sectionIdOfAnchor', $this->getSectionIdOfAnchor(...), ['needs_context' => true]),
            new TwigFunction('isSitemap', $this->isSitemap(...)),
            new TwigFunction('changelogMetadata', $this->getChangelogMetadata(...), ['needs_context' => true]),
            new TwigFunction('pagePermalink', $this->getPagePermalink(...), ['needs_context' => true]),
            new TwigFunction('markdownVersion', $this->getMarkdownVersion(...), ['needs_context' => true]),
            new TwigFunction('markdownIsStartPage', $this->isMarkdownStartPage(...), ['needs_context' => true]),
            new TwigFunction('getViewSourceLink', $this->getViewSourceLink(...), ['needs_context' => true]),
            new TwigFunction('getRelativePath', $this->getRelativePath(...), ['needs_context' => true]),
            new TwigFunction('getPagerLinks', $this->getPagerLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getPrevNextLinks', $this->getPrevNextLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getSettings', $this->getSettings(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getTYPO3Version', $this->getTYPO3Version(...), ['is_safe' => ['html'], 'needs_context' => false]),
            new TwigFunction('isNoSearch', $this->isNoSearch(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('copyDownload', $this->copyDownload(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getStandardInventories', $this->getStandardInventories(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getRstCodeForLink', $this->getRstCodeForLink(...), ['is_safe' => [], 'needs_context' => true]),
            new TwigFunction('isRenderedForDeployment', $this->isRenderedForDeployment(...)),
            new TwigFunction('replaceLineBreakOpportunityTags', $this->replaceLineBreakOpportunityTags(...), ['is_safe' => ['html'], 'needs_context' => false]),
            new TwigFunction('filterAllowedSearchFacets', $this->filterAllowedSearchFacets(...), ['is_safe' => ['html'], 'needs_context' => false]),
            new TwigFunction('getPermalink', $this->getPermalink(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getSingleHtmlLink', $this->getSingleHtmlLink(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getTopPageLink', $this->getTopPageLink(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('setBackAnchor', $this->setBackAnchor(...), ['needs_context' => true]),
            new TwigFunction('getBackAnchor', $this->getBackAnchor(...), ['needs_context' => true]),
        ];
    }

    public function filterAllowedSearchFacets(string $value): string
    {
        $allowed = [
            'TypoScript',
            'TSconfig',
            'ViewHelper',
            'TCA',
            'TYPO3_CONF_VAR',
            'YAML Form Setting',
            'YAML RTE Setting',
            'Site Language Configuration',
            'Site Configuration',
            'Console Command',
            'Console Command Argument',
            'Console Command Option',
            'File',
            'Directory',
            SiteSetSettingsDirective::FACET,
        ];
        if (!in_array(trim($value), $allowed, true)) {
            return 'Option';
        }
        return $value;
    }
    public function replaceLineBreakOpportunityTags(string $value): string
    {
        // as the result is html safe
        $brokenValue = htmlspecialchars($value);
        $brokenValue  = preg_replace(self::BRACKETS_BREAK_REGEX, '<wbr>$1', $brokenValue);
        $brokenValue  = preg_replace(self::CAMEL_CASE_BREAK_REGEX, '$1<wbr>$2', $brokenValue ?? $value);
        $brokenValue  = preg_replace(self::NON_LETTER_BREAK_REGEX, '$1<wbr>$2', $brokenValue ?? $value);
        $brokenValue  = preg_replace(self::NON_LETTER_BREAK_REGEX, '$1<wbr>$2', $brokenValue ?? $value);
        return $brokenValue ?? $value;
    }

    public function renderPlainText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            $string = '';
            foreach ($value as $child) {
                $string .= $this->renderPlainText($child);
            }
            return $string;
        }
        if ($value instanceof LinkTargetNode) {
            return $this->renderPlainText($value->getLinkText());
        }
        if ($value instanceof Node) {
            return $this->renderPlainText($value->getValue());
        }
        if (is_object($value)) {
            throw new \Exception('Cannot render object ' . get_class($value) . ' as plaintext.');
        } else {
            throw new \Exception('Cannot render type ' . gettype($value) . ' as plaintext.');
        }
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function isNoSearch(array $context): bool
    {
        $renderContext = $this->getRenderContext($context);
        try {
            if ($renderContext->getCurrentDocumentEntry() === null) {
                return false;
            }
            $document = $renderContext->getDocumentNodeForEntry($renderContext->getCurrentDocumentEntry());
        } catch (\Exception) {
            return false;
        }
        $headerNodes = $document->getHeaderNodes();
        foreach ($headerNodes as $headerNode) {
            if ($headerNode instanceof NoSearchNode) {
                return true;
            }
            // Disable searching on orphans
            if ($headerNode instanceof OrphanNode) {
                return true;
            }
            // Disable searching on sitemaps
            if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'sitemap.html') {
                return true;
            }
            // Disable searching on changelog indexes
            if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'changelogOverview.html') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getRstCodeForLink(array $context, LinkTargetNode $linkTargetNode): string
    {
        $interlink = $this->themeSettings->getSettings('interlink_shortcode') !== '' ? $this->themeSettings->getSettings('interlink_shortcode') : 'somemanual';
        if ($linkTargetNode instanceof Typo3FileNode) {
            return sprintf(
                ':file:`%s`',
                $linkTargetNode->getLinkText(),
            );
        }
        if ($linkTargetNode instanceof PrefixedLinkTargetNode && $linkTargetNode->getLinkType() === ConfvalNode::LINK_TYPE) {
            return sprintf(
                ':ref:`%s <%s:%s%s>`',
                $linkTargetNode->getLinkText(),
                $interlink,
                $linkTargetNode->getPrefix(),
                $linkTargetNode->getId()
            );
        }
        if ($linkTargetNode instanceof PrefixedLinkTargetNode && $linkTargetNode->getLinkType() === ViewHelperNode::LINK_TYPE) {
            return sprintf(
                ':ref:`%s <%s:%s%s>`',
                $linkTargetNode->getLinkText(),
                $interlink,
                $linkTargetNode->getPrefix(),
                $linkTargetNode->getId()
            );
        }
        if ($linkTargetNode instanceof PrefixedLinkTargetNode && $linkTargetNode->getLinkType() === ViewHelperArgumentNode::LINK_TYPE) {
            return sprintf(
                ':ref:`%s <%s:%s%s>`',
                $linkTargetNode->getLinkText(),
                $interlink,
                $linkTargetNode->getPrefix(),
                $linkTargetNode->getId()
            );
        }
        if ($linkTargetNode instanceof PhpComponentNode) {
            return sprintf(
                ':%s:`%s:%s`',
                $linkTargetNode->getLinkType(),
                $interlink,
                $linkTargetNode->getName()->toString()
            );
        }
        if ($linkTargetNode instanceof PhpMemberNode) {
            return sprintf(
                ':%s:`%s:%s`',
                $linkTargetNode->getLinkType(),
                $interlink,
                $linkTargetNode->getFullyQualifiedName()
            );
        }
        return '';
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getAnchorIdOfSection(array $context, SectionNode $sectionNode): string
    {
        foreach ($sectionNode->getChildren() as $childNode) {
            if ($childNode instanceof AnchorNode) {
                return $this->anchorNormalizer->reduceAnchor($childNode->toString());
            }
        }
        return '';
    }

    /**
     * The ids a link inside the single Markdown file may point at for this
     * section.
     *
     * Markdown has no way to give a heading an id, so the single-file output
     * writes an empty HTML anchor before each one. Which id a link carries
     * depends on how it was written -- an explicit label, or the heading it
     * points at -- so both are offered, and a section may end up with two.
     *
     * Both have to be registered as a "std:label" to be written, which is the
     * same question getMarkdownLinkUrl() asks before it turns a link inward.
     * ProjectNode::addInternalTarget() refuses a second "std:label" of the same
     * name with an exception and waves "std:title" through, so a registered
     * label is unique across the manual while a bare heading id is not: the
     * TYPO3 Core API manual has "Configuration" as a heading often enough that
     * anchors built from it would have collided nineteen times.
     *
     * Asking the same question on both sides is what keeps the two in step. An
     * id that fails it is written by neither: no anchor here, and a permalink
     * rather than a "#" over there.
     *
     * @param array{env: RenderContext} $context
     * @return list<string>
     */
    public function getMarkdownSectionAnchors(array $context, SectionNode $sectionNode): array
    {
        $projectNode = $this->getRenderContext($context)->getProjectNode();

        $candidates = [];
        foreach ($sectionNode->getChildren() as $childNode) {
            if ($childNode instanceof AnchorNode) {
                $candidates[] = $childNode->toString();
            }
        }

        $candidates[] = $sectionNode->getTitle()->getId();

        $anchors = [];
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $anchor = $this->anchorNormalizer->reduceAnchor($candidate);
            if (!AddressableAnchors::isAddressable($projectNode, $anchor)) {
                continue;
            }

            $anchors[] = $anchor;
        }

        return array_values(array_unique($anchors));
    }

    /**
     * The id a heading carries: in the HTML section it sits in, in the
     * "#" its permalink button offers, and in the per-page Markdown.
     *
     * The explicit label of the section where it has one, and the id derived
     * from the heading otherwise. The label is what a permalink resolves
     * against, and it is what the author wrote down; the derived id is
     * generated, and generated second -- a section labelled ".. _session-data:"
     * under the heading "Session data" yields the id "session-data-1", because
     * the label already holds "session-data". Writing that into the Markdown
     * named the page by its accident rather than by its name.
     *
     * The HTML keeps both -- the id on the section, the label beside it as
     * "data-rst-anchor" -- because a page there can carry two. A Markdown
     * heading carries one, so it carries the one that means something.
     *
     * Safe to change: the per-page Markdown links by permalink and writes no
     * "#" of its own, so no link in it points at the id this replaces.
     *
     * @param array{env: RenderContext} $context
     */
    public function getHeadingId(array $context, TitleNode $titleNode): string
    {
        $renderContext = $this->getRenderContext($context);
        $document = $this->currentDocument($renderContext);
        $section = $document === null ? null : $this->sectionOf($document, $titleNode);
        if ($section !== null) {
            $label = $this->getAnchorIdOfSection($context, $section);
            if ($label !== '') {
                return $label;
            }
        }

        return $titleNode->getId();
    }

    /**
     * The id of the section this anchor gave its name to, or "" when it gave it
     * to none.
     *
     * A section with a label is written with that label as its id, so the empty
     * anchor element the library renders for the same label would repeat it --
     * and an id may occur once in a document. The template asks this before
     * writing the anchor, and leaves it out when the section already carries
     * the very same id.
     *
     * The comparison is on the id, not on the anchor being first: a label is
     * normalised on its way into the section id, so an anchor whose spelling
     * survives that differently still has to be written, or the name it defines
     * would be gone from the page.
     *
     * @param array{env: RenderContext} $context
     */
    public function getSectionIdOfAnchor(array $context, AnchorNode $anchorNode): string
    {
        $renderContext = $this->getRenderContext($context);
        $document = $this->currentDocument($renderContext);
        $section = $document === null ? null : $this->sectionOfAnchor($document, $anchorNode);
        if ($section === null) {
            return '';
        }

        return $this->getAnchorIdOfSection($context, $section);
    }

    /**
     * The section holding this anchor as a direct child, or null.
     *
     * Only a direct child names a section; an anchor further down the page
     * belongs to whatever follows it and keeps its element.
     *
     * @param CompoundNode<Node> $node
     */
    private function sectionOfAnchor(CompoundNode $node, AnchorNode $anchorNode): SectionNode|null
    {
        foreach ($node->getChildren() as $child) {
            if (!$child instanceof SectionNode) {
                continue;
            }

            foreach ($child->getChildren() as $sectionChild) {
                if ($sectionChild === $anchorNode) {
                    return $child;
                }
            }

            $found = $this->sectionOfAnchor($child, $anchorNode);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * The section a heading belongs to, found by identity.
     *
     * The template that renders a heading is handed the title alone, and a
     * title does not know its section. Walking the document to find it again
     * keeps this stateless, which matters more here than the cost: a page has
     * tens of sections, not thousands.
     *
     * @param CompoundNode<Node> $node
     */
    private function sectionOf(CompoundNode $node, TitleNode $titleNode): SectionNode|null
    {
        foreach ($node->getChildren() as $child) {
            if ($child instanceof SectionNode) {
                if ($child->getTitle() === $titleNode) {
                    return $child;
                }

                $found = $this->sectionOf($child, $titleNode);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Whether this document is the sitemap of the manual.
     *
     * A sitemap holds nothing but its title in the source; the page tree below
     * it is assembled by "sitemap.html". Markdown does not use that template,
     * so the page would contribute a heading with nothing under it -- and a
     * list of every page is what the single Markdown file already is.
     *
     * Recognised by ":template: sitemap.html", the same marker isNoSearch()
     * uses to keep sitemaps out of the search index. Deliberately this one
     * template rather than any ":template:": another such page may well carry
     * content worth having.
     */
    public function isSitemap(DocumentNode $document): bool
    {
        foreach ($document->getHeaderNodes() as $headerNode) {
            if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'sitemap.html') {
                return true;
            }
        }

        return false;
    }

    /**
     * What a TYPO3 Core Changelog entry says about itself beyond its title, for
     * the front matter of its Markdown. Returns [] for every other manual.
     *
     * An entry carries things no other page does, and every one of them is lost
     * on the way into Markdown: the release the change went into, which is the
     * first thing anybody asks of a changelog entry, and its major on its own as
     * a number, so that filtering for everything that landed in TYPO3 13 does
     * not mean matching a string prefix against "13.0" through "13.4.x"; the
     * kind of change -- breaking, feature, deprecation, important -- which is
     * what a reader filters on first, with the Forge issue and its URL; and the
     * tags the entry is categorised by.
     *
     * Read by ChangelogEntry, which the JSON index reads as well, so the front
     * matter of an entry and the line about it in the index cannot disagree.
     *
     * @param array{env: RenderContext} $context
     * @return array<string, string|int|list<string>>
     */
    public function getChangelogMetadata(array $context): array
    {
        if ($this->themeSettings->getSettings('interlink_shortcode') !== 'changelog') {
            return [];
        }

        $document = $this->currentDocument($this->getRenderContext($context));
        if ($document === null) {
            return [];
        }

        $metadata = [];

        $version = $this->changelogEntry->version($document);
        if ($version !== '') {
            $metadata['typo3-version'] = $version;
            $metadata['typo3-major'] = (int) $version;
        }

        $kind = $this->changelogEntry->kind($document);
        if ($kind !== null) {
            $metadata['type'] = $kind['type'];
            $metadata['issue'] = $kind['issue'];
            $metadata['forge'] = self::FORGE_ISSUE_URL . $kind['issue'];
        }

        $tags = $this->changelogEntry->tags($document);
        if ($tags !== []) {
            $metadata['tags'] = $tags;
        }

        return $metadata;
    }

    /** The document being rendered, or null when it cannot be had. */
    private function currentDocument(RenderContext $renderContext): DocumentNode|null
    {
        $entry = $renderContext->getCurrentDocumentEntry();
        if ($entry === null) {
            return null;
        }

        try {
            return $renderContext->getDocumentNodeForEntry($entry);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getPermalink(array $context, SectionNode $sectionNode): string
    {
        $renderContext = $this->getRenderContext($context);
        $interlink = $this->themeSettings->getSettings('interlink_shortcode');
        if ($interlink === '') {
            $this->logger->warning('A permalink can only be generated if "interlink_shortcode" is set in the guides.xml. ', $renderContext->getLoggerInformation());
            return '';
        }
        $anchorId = '';
        foreach ($sectionNode->getChildren() as $childNode) {
            if ($childNode instanceof AnchorNode) {
                $anchorId = $this->anchorNormalizer->reduceAnchor($childNode->toString());
                break;
            }
        }
        if ($anchorId === '') {
            $this->logger->warning('The surrounding section has no anchor. ', $renderContext->getLoggerInformation());
        }
        return 'https://docs.typo3.org/permalink/' . $interlink . ':' . $anchorId;
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getEditOnGitHubLinkFromPath(array $context, ?string $path): string
    {
        if (($path ?? '') === '') {
            return '';
        }
        $githubButton = $this->themeSettings->getSettings('edit_on_github');
        if ($githubButton === '') {
            return '';
        }
        $githubBranch = $this->themeSettings->getSettings('edit_on_github_branch', 'main');
        $currentFileName = $this->getCurrentFilename($context);
        if ($currentFileName === '') {
            return '';
        }
        $githubDirectory = trim($this->themeSettings->getSettings('edit_on_github_directory', 'Documentation'), '/');
        return sprintf("https://github.com/%s/edit/%s/%s%s", $githubButton, $githubBranch, $githubDirectory, $path);
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getEditOnGitHubLink(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        $githubButton = $this->themeSettings->getSettings('edit_on_github');
        if ($githubButton === '') {
            return '';
        }
        $githubBranch = $this->themeSettings->getSettings('edit_on_github_branch', 'main');
        $sourceFile = $this->getSourceFilename($context);
        if ($sourceFile === '') {
            return '';
        }
        $gitHubPerPageLink = $this->getEditOnGitHubLinkPerPage($renderContext);

        $githubDirectory = trim($this->themeSettings->getSettings('edit_on_github_directory', 'Documentation'), '/');
        return $gitHubPerPageLink ?? sprintf("https://github.com/%s/edit/%s/%s/%s", $githubButton, $githubBranch, $githubDirectory, $sourceFile);
    }

    /**
     * The page's source file on the forge it is maintained in.
     *
     * The rendered output no longer ships the reStructuredText itself -- it
     * lives in the project's repository, which is where a reader following
     * "view source" wants to end up anyway, with history and blame attached.
     *
     * Returns an empty string when no repository is configured, in which case
     * the menu entry is left out rather than pointing nowhere.
     *
     * @param array{env: RenderContext} $context
     */
    public function getViewSourceLink(array $context): string
    {
        $sourceFile = $this->getSourceFilename($context);
        if ($sourceFile === '') {
            return '';
        }

        $branch = $this->themeSettings->getSettings('edit_on_github_branch', 'main');
        $directory = trim($this->themeSettings->getSettings('edit_on_github_directory', 'Documentation'), '/');

        $github = $this->themeSettings->getSettings('edit_on_github');
        if ($github !== '') {
            return sprintf('https://github.com/%s/blob/%s/%s/%s', $github, $branch, $directory, $sourceFile);
        }

        // No GitHub setting exists for GitLab, but "project_repository" already
        // carries the repository URL and is validated for the same hosts as the
        // issue links.
        $repository = rtrim($this->themeSettings->getSettings('project_repository'), '/');
        if (str_starts_with($repository, 'https://gitlab.com/')) {
            return sprintf('%s/-/blob/%s/%s/%s', $repository, $branch, $directory, $sourceFile);
        }

        if (str_starts_with($repository, 'https://github.com/')) {
            return sprintf('%s/blob/%s/%s/%s', $repository, $branch, $directory, $sourceFile);
        }

        return '';
    }

    private function getEditOnGitHubLinkPerPage(RenderContext $renderContext): string|null
    {
        try {
            if ($renderContext->getCurrentDocumentEntry() === null) {
                return null;
            }
            $document = $renderContext->getDocumentNodeForEntry($renderContext->getCurrentDocumentEntry());
        } catch (\Exception) {
            return null;
        }
        $headerNodes = $document->getHeaderNodes();
        foreach ($headerNodes as $headerNode) {
            if ($headerNode instanceof EditOnGitHubNode) {
                return $headerNode->toString();
            }
        }
        return null;
    }


    /**
     * @param array{env: RenderContext} $context
     */
    public function getReportIssueLink(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        $reportButton = $this->themeSettings->getSettings('report_issue');
        if ($reportButton === 'none') {
            return '';
        }
        if (str_starts_with($reportButton, '/')) {
            return $this->urlGenerator->generateCanonicalOutputUrl($renderContext, $reportButton);
        }
        if (str_starts_with($reportButton, 'https://forge.typo3.org/')) {
            $reportButton = $this->enrichForgeLink($reportButton, $renderContext);
            return $reportButton;
        }
        if (str_starts_with($reportButton, 'https://github.com/')) {
            $reportButton = $this->enrichGithubReport($reportButton, $renderContext);
            return $reportButton;
        }
        if (str_starts_with($reportButton, 'https://gitlab.com/')) {
            return $reportButton;
        }
        if (str_starts_with($reportButton, 'https://bitbucket.org/')) {
            $reportButton = $this->enrichBitbuckedReport($reportButton, $renderContext);
            return $reportButton;
        }
        if ($reportButton !== '') {
            $this->logger->warning(
                'For security reasons only "report-issue" links in the guides.xml
                to a local page (starting with "/") or to one of these 4 platforms
                are allowed: https://forge.typo3.org/ https://github.com/ https://gitlab.com/
                https://bitbucket.org/'
            );
            return '';
        }

        $reportButton = $this->themeSettings->getSettings('project_issues');
        $reportButton = rtrim($reportButton, '/');

        if ($reportButton === '') {
            return '';
        }
        if (str_starts_with($reportButton, 'https://forge.typo3.org/')) {
            $reportButton = $this->enrichForgeLink($reportButton, $renderContext);
            return $reportButton;
        }
        if (str_starts_with($reportButton, 'https://github.com/')) {
            $reportButton = $this->enrichGithubReport($reportButton, $renderContext);
            return $reportButton;
        }
        if (str_starts_with($reportButton, 'https://gitlab.com/')) {
            if (str_ends_with($reportButton, '/issues')) {
                $reportButton .= '/new';
            }
            return $reportButton;
        }

        $this->logger->warning('For security reasons only only "project_issues" links in the guides.xml to one of these 3 plattforms are allowed: https://forge.typo3.org/ https://github.com/ https://gitlab.com/');
        return '';
    }

    public function enrichGithubReport(string $reportButton, RenderContext $renderContext): string
    {
        if (str_ends_with($reportButton, '/issues')) {
            $reportButton .= '/new/choose';
        }
        if (str_ends_with($reportButton, '/new/choose') or str_ends_with($reportButton, '/new')) {
            $reportButton .= '?title=';
            $description = $this->getIssueTitle($renderContext);
            $reportButton .= urlencode($description);
        }
        return $reportButton;
    }



    private function enrichBitbuckedReport(string $reportButton, RenderContext $renderContext): string
    {
        if (str_ends_with($reportButton, '/issues')) {
            $reportButton .= '/new';
        }
        if (str_ends_with($reportButton, '/new')) {
            $reportButton .= '?title=';
            $description = $this->getIssueTitle($renderContext);
            $reportButton .= urlencode($description);
        }
        return $reportButton;
    }

    /**
     * @param string $reportButton
     * @param RenderContext $renderContext
     * @return string
     */
    public function enrichForgeLink(string $reportButton, RenderContext $renderContext): string
    {
        if (str_ends_with($reportButton, '/issues')) {
            $reportButton .= '/new';
        }
        if (str_ends_with($reportButton, '/new')) {
            $reportButton .= '?issue[category_id]=1004&issue[subject]=';
            $version = $this->typo3VersionService->getPreferredVersion();
            $extension = $this->themeSettings->getSettings('interlink_shortcode');
            if ($extension === 'changelog') {
                $extension = 'typo3/cms-core';
            }
            $description = $this->getIssueTitle(
                $renderContext,
                sprintf(
                    'https://docs.typo3.org/c/%s/%s/en-us',
                    $extension,
                    $version,
                )
            );
            $reportButton .= urlencode($description);
            switch ($version) {
                case 'main':
                    $reportButton .= '&issue[custom_field_values][4]=' . Typo3VersionMapping::getMajorVersionOfMain()->value;
                    break;
                case '13.4':
                    $reportButton .= '&issue[custom_field_values][4]=13';
                    break;
                case '12.4':
                    $reportButton .= '&issue[custom_field_values][4]=12';
                    break;
                case '11.5':
                    $reportButton .= '&issue[custom_field_values][4]=11';
                    break;
            }
        }
        return $reportButton;
    }

    /**
     * @param RenderContext $renderContext
     * @return string
     */
    public function getIssueTitle(RenderContext $renderContext, ?string $docsPath = null): string
    {
        return sprintf(
            'Problem on %s/%s.html',
            $docsPath ?? $this->themeSettings->getSettings('project_home'),
            $renderContext->getCurrentFileName()
        );
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<string>
     */
    public function getStandardInventories(array $context): array
    {
        $outputArray = array_map(fn($value) => $value->value, DefaultInventories::cases());
        sort($outputArray, SORT_STRING);

        return $outputArray;
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getCurrentFilename(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        try {
            return $renderContext->getCurrentFileName();
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Turn a link in the Markdown output into a permalink.
     *
     * A Markdown file is meant to be downloaded and read away from the site it
     * came from, so a relative link would be dead on arrival. Every internal
     * target carries an anchor, which is exactly what the permalink service
     * resolves, so "interlink_shortcode" plus that anchor is enough to build a
     * URL that keeps working -- and keeps working across versions, which a
     * hard-coded manual URL would not.
     *
     * Left alone: anything with a scheme (external links, mailto:) and links
     * without an anchor, which in practice are images. Without
     * "interlink_shortcode" no permalink can be built, so those fall back to
     * the relative HTML page.
     *
     * @param array{env: RenderContext} $context
     */
    public function getMarkdownLinkUrl(array $context, string $url): string
    {
        if ($url === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1) {
            return $url;
        }

        $anchorPosition = strpos($url, '#');
        $anchor = $anchorPosition === false ? '' : substr($url, $anchorPosition + 1);
        $interlink = $this->themeSettings->getSettings('interlink_shortcode');

        if ($anchor === '') {
            // "#" is a reference to the page being rendered. That is fine in a
            // browser, but a downloaded file should still point somewhere, so
            // it becomes the permalink of this very page.
            $anchor = $url === '#'
                ? ($this->getRenderContext($context)->getCurrentDocumentEntry()?->getTitle()->getId() ?? '')
                : $this->anchorOfLinkedDocument($context, $url);
        }

        // The fragment of a rendered URL keeps the casing of the element id it
        // points at, which HTML resolves fine. The permalink service looks the
        // target up in the inventory, where it is registered normalised, so the
        // anchor has to be reduced the same way getPermalink() does it.
        if ($anchor !== '') {
            $anchor = $this->anchorNormalizer->reduceAnchor($anchor);
        }

        // In the single file every page of this manual is present, so a link to
        // one of them belongs inside the document rather than out on the web.
        //
        // Only if the target is a registered label, though. A label is unique
        // across the manual -- addInternalTarget() refuses a second one -- so
        // the anchor written for it is unambiguous. Anything else, a heading id
        // in particular, may occur in a dozen documents, and an anchor built
        // from it would silently resolve to the first of them. Those keep the
        // permalink, which at least lands where it says.
        $renderContext = $this->getRenderContext($context);
        if (
            $anchor !== ''
            && $renderContext->getOutputFormat() === 'singlemd'
            && AddressableAnchors::isAddressable($renderContext->getProjectNode(), $anchor)
        ) {
            return '#' . $anchor;
        }

        if ($anchor === '' || $interlink === '') {
            $this->logUnresolvedMarkdownLink($context, $url, $interlink === '');

            // The URL generator appended the current output format, so an
            // internal link reads "Feature.md" here -- or "Feature.singlemd"
            // when the whole project is rendered into one file; the HTML page
            // is the one worth pointing at in either case.
            return preg_replace('/\.(?:single)?md(?=$|#)/', '.html', $url) ?? $url;
        }

        return $this->permalinks->forAnchor($anchor, $this->rawProjectVersion($this->getRenderContext($context)));
    }

    /**
     * The project version a permalink and a metadata field can carry, or "" when
     * there is none. Spelled by Permalinks; reported here, because only a page
     * being rendered can say which file the unusable version was found in.
     */
    private function normalizedProjectVersion(RenderContext $renderContext): string
    {
        $raw = $this->rawProjectVersion($renderContext);
        $version = $this->permalinks->normalizeVersion($raw);
        if ($version === '' && trim((string) $raw) !== '') {
            $this->reportUnusablePermalinkVersion($renderContext, explode(' ', trim((string) $raw))[0]);
        }

        return $version;
    }

    private function rawProjectVersion(RenderContext $renderContext): string|null
    {
        return $renderContext->getProjectNode()->getVersion();
    }

    /**
     * The permalink of the document being rendered: the one URL that names this
     * page no matter where its file ends up or what the page is later renamed
     * to. "" when the manual declares no interlink shortcode, or the page has
     * no anchor to be named by.
     *
     * Written into the Markdown front matter, and into the HTML head as the
     * canonical URL of the page.
     *
     * @param array{env: RenderContext} $context
     */
    public function getPagePermalink(array $context): string
    {
        $interlink = $this->themeSettings->getSettings('interlink_shortcode');
        $renderContext = $this->getRenderContext($context);

        // The head is rendered for things that are not a document too -- the
        // sitemap and the main menu among them -- and a page is what a permalink
        // names. Asking for the current entry there throws.
        if (!$renderContext->hasCurrentFileName()) {
            return '';
        }

        $entry = $renderContext->getCurrentDocumentEntry();
        $anchor = $entry === null ? '' : $this->documentAnchor($renderContext, $entry);
        if ($interlink === '' || $anchor === '') {
            return '';
        }

        return $this->permalinks->forAnchor($anchor, $this->rawProjectVersion($renderContext));
    }

    /** @param array{env: RenderContext} $context */
    public function getMarkdownVersion(array $context): string
    {
        return $this->normalizedProjectVersion($this->getRenderContext($context));
    }

    /**
     * Whether the document being rendered is the manual's start page.
     *
     * Worth stating rather than leaving to be guessed: once the files lie
     * flat in a directory, the entry point is no longer the one at the top,
     * and "Index.rst" is a name several documents in a manual share.
     *
     * @param array{env: RenderContext} $context
     */
    public function isMarkdownStartPage(array $context): bool
    {
        $renderContext = $this->getRenderContext($context);
        $entry = $renderContext->getCurrentDocumentEntry();
        if ($entry === null) {
            return false;
        }

        try {
            return $entry->getFile() === $renderContext->getProjectNode()->getRootDocumentEntry()->getFile();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Reported once per document: the version is a property of the project, so
     * every link on the page is missing it for the same reason.
     */
    private function reportUnusablePermalinkVersion(RenderContext $renderContext, string $version): void
    {
        $document = $renderContext->hasCurrentFileName() ? $renderContext->getCurrentFileName() : '';
        if (isset($this->reportedMissingVersion[$document])) {
            return;
        }

        $this->reportedMissingVersion[$document] = true;
        $this->logger->warning(
            sprintf(
                'The version "%s" from the guides.xml cannot go into a URL, so the Markdown permalinks of this '
                . 'manual resolve to its latest stable release instead of to this version. ',
                $version,
            ),
            $renderContext->getLoggerInformation(),
        );
    }

    /**
     * A link left relative in the Markdown output is dead once the file is
     * downloaded, so it is worth reporting rather than shipping quietly.
     *
     * A missing "interlink_shortcode" affects every link on the page alike, so
     * it is reported once per document instead of once per link; an
     * unresolvable target is specific to that link and is reported each time.
     *
     * @param array{env: RenderContext} $context
     */
    private function logUnresolvedMarkdownLink(array $context, string $url, bool $missingShortcode): void
    {
        $renderContext = $this->getRenderContext($context);

        if ($missingShortcode) {
            $document = $renderContext->hasCurrentFileName() ? $renderContext->getCurrentFileName() : '';
            if (isset($this->reportedMissingShortcode[$document])) {
                return;
            }

            $this->reportedMissingShortcode[$document] = true;

            // Not a warning: a manual without "interlink-shortcode" cannot have
            // permalinks at all, so this describes how the project is set up
            // rather than something broken in the page. Warning per document
            // would drown the links that genuinely failed to resolve.
            $this->logger->info(
                'Links in the Markdown output stay relative because "interlink-shortcode" is not set in the guides.xml. ',
                $renderContext->getLoggerInformation(),
            );

            return;
        }

        $this->logger->warning(
            sprintf('The Markdown link to "%s" could not be resolved to a permalink and stays relative. ', $url),
            $renderContext->getLoggerInformation(),
        );
    }

    /**
     * The anchor of a page linked without one, so it too can become a permalink.
     *
     * A ":doc:" reference points at a page rather than a label, so the
     * generated URL carries no fragment. The target is in this same manual,
     * though, so its document entry is known and its title carries the anchor.
     *
     * Returns an empty string for anything that is not a document of this
     * manual -- an image, most commonly.
     *
     * @param array{env: RenderContext} $context
     */
    private function anchorOfLinkedDocument(array $context, string $url): string
    {
        $renderContext = $this->getRenderContext($context);
        $path = preg_replace('/\.[A-Za-z0-9]+$/', '', $url);
        if ($path === null || $path === '') {
            return '';
        }

        try {
            $canonical = $this->documentNameResolver->canonicalUrl($renderContext->getDirName(), $path);
            $entry = $renderContext->getProjectNode()->findDocumentEntry($canonical);
        } catch (Throwable) {
            return '';
        }

        return $entry === null ? '' : $this->documentAnchor($renderContext, $entry);
    }

    /**
     * The anchor that identifies one document, taken from its own label.
     *
     * Not the title's id: titles repeat, and the pipeline exempts "std:title"
     * from its duplicate-anchor check for exactly that reason, so only one of
     * several pages sharing a title ends up registered under it. The TYPO3
     * changelog shows what that costs -- the same entry backported to three
     * versions carries three distinct labels ("breaking-84843",
     * "breaking-84843-1668719172", "breaking-84843-1668719171") but a single
     * title anchor, which resolves to whichever of the three won. Building a
     * permalink from the title would silently point at the wrong version.
     *
     * Falls back to the title id when a document declares no label of its own,
     * which is the best available identifier in that case.
     */
    private function documentAnchor(RenderContext $renderContext, DocumentEntryNode $entry): string
    {
        try {
            $document = $renderContext->getDocumentNodeForEntry($entry);
        } catch (Throwable) {
            $document = null;
        }

        foreach ($document?->getChildren() ?? [] as $child) {
            if (!$child instanceof SectionNode) {
                continue;
            }

            foreach ($child->getChildren() as $sectionChild) {
                if ($sectionChild instanceof AnchorNode) {
                    return $this->anchorNormalizer->reduceAnchor($sectionChild->toString());
                }
            }

            break;
        }

        $id = $entry->getTitle()->getId();

        return $id === '' ? '' : $this->anchorNormalizer->reduceAnchor($id);
    }

    /**
     * The Markdown rendering of the current page, which is written next to the
     * HTML file with the same base name.
     *
     * Machine consumers want the content without the surrounding HTML; the
     * Markdown has includes, substitutions and interlinks resolved, which the
     * reStructuredText source does not.
     *
     * @param array{env: RenderContext} $context
     */
    /**
     * The name the Markdown of this page is saved under.
     *
     * Built from the same two parts as its permalink, the manual's
     * "interlink_shortcode" and the page's anchor, so a file picked out of a
     * download folder still says which page of which manual it is -- and files
     * collected from several manuals cannot collide, where every overview page
     * would otherwise arrive as "index.md" and overwrite the last.
     *
     * Returns an empty string when either part is missing, which leaves the
     * browser to name the file from the URL as before.
     *
     * @param array{env: RenderContext} $context
     */
    public function getMarkdownDownloadName(array $context): string
    {
        if ($this->getMarkdownAlternate($context) === '') {
            return '';
        }

        $interlink = $this->themeSettings->getSettings('interlink_shortcode');
        $renderContext = $this->getRenderContext($context);
        $entry = $renderContext->getCurrentDocumentEntry();
        $anchor = $entry === null ? '' : $this->documentAnchor($renderContext, $entry);
        if ($interlink === '' || $anchor === '') {
            return '';
        }

        $prefix = $this->anchorNormalizer->reduceAnchor($interlink);

        // Changelog anchors already carry the manual's own name; repeating it
        // would read as "changelog-changelog-feature-...".
        if ($anchor === $prefix || str_starts_with($anchor, $prefix . '-')) {
            return $anchor . '.md';
        }

        return $prefix . '-' . $anchor . '.md';
    }

    /**
     * The Markdown rendering of the current page, which is written next to the
     * HTML file with the same base name.
     *
     * Machine consumers want the content without the surrounding HTML; the
     * Markdown has includes, substitutions and interlinks resolved, which the
     * reStructuredText source does not.
     *
     * @param array{env: RenderContext} $context
     */
    public function getMarkdownAlternate(array $context): string
    {
        // No Markdown is written when the project opted out, so neither the
        // head link nor the menu entry may promise one.
        $renderMarkdown = strtolower(trim($this->themeSettings->getSettings('render_markdown', 'true')));
        if (in_array($renderMarkdown, ['', 'false', '0', 'off', 'no'], true)) {
            return '';
        }

        $renderContext = $this->getRenderContext($context);
        if (!$renderContext->hasCurrentFileName()) {
            return '';
        }

        return basename($renderContext->getCurrentFileName()) . '.md';
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getSourceFilename(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        return $renderContext->hasCurrentFileName() ? $renderContext->getDocument()->getOption('originalFileName', $renderContext->getCurrentFileName()) ?? '' : '';
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getRelativePath(array $context, string $path): string
    {
        $renderContext = $this->getRenderContext($context);
        if ($this->typo3AzureEdgeURI !== '') {
            // CI (GitHub Actions) gets special treatment, then we use a fixed URI for assets.
            // TODO: Fixate the "_resources" string as a class/config constant, not hardcoded
            // (see packages/typo3-docs-theme/src/EventListeners/CopyResources.php)
            return str_replace('/_resources/', '/', $this->typo3AzureEdgeURI . $path);
        } else {
            return $this->urlGenerator->generateInternalUrl($context['env'] ?? null, $path);
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return string
     */
    public function copyDownload(
        array  $context,
        string $sourcePath,
        string $targetPath
    ): string {
        $outputPath = $this->copyAsset($context['env'] ?? null, $sourcePath, $targetPath);
        $relativePath = $this->urlGenerator->generateInternalUrl($context['env'] ?? null, trim($outputPath, '/'));
        // make it relative so it plays nice with the base tag in the HEAD
        return $relativePath;
    }

    private function copyAsset(
        RenderContext|null $renderContext,
        string             $sourcePath,
        string             $targetPath
    ): string {
        if (!$renderContext instanceof RenderContext) {
            return $sourcePath;
        }

        $canonicalUrl = $this->documentNameResolver->canonicalUrl($renderContext->getDirName(), $sourcePath);
        $outputPath = $this->documentNameResolver->absoluteUrl(
            $renderContext->getDestinationPath(),
            $targetPath,
        );

        try {
            if ($renderContext->getOrigin()->has($sourcePath) === false) {
                $this->logger->error(
                    sprintf('Download not found "%s"', $sourcePath),
                    $renderContext->getLoggerInformation(),
                );

                return $outputPath;
            }

            $fileContents = $renderContext->getOrigin()->read($sourcePath);
            if ($fileContents === false) {
                $this->logger->error(
                    sprintf('Could not read download file "%s"', $sourcePath),
                    $renderContext->getLoggerInformation(),
                );

                return $outputPath;
            }

            $result = $renderContext->getDestination()->put($outputPath, $fileContents);
            if ($result === false) {
                $this->logger->error(
                    sprintf('Unable to write file "%s"', $outputPath),
                    $renderContext->getLoggerInformation(),
                );
            }
        } catch (LogicException|FilesystemException $e) {
            $this->logger->error(
                sprintf('Unable to write file "%s", %s', $outputPath, $e->getMessage()),
                $renderContext->getLoggerInformation(),
            );
        }

        return $outputPath;
    }

    /**
     * @param array{env: RenderContext} $context
     * @return string
     */
    public function getSettings(array $context, string $key, string $default = ''): string
    {
        return $this->themeSettings->getSettings($key, $default);
    }

    public function getTYPO3Version(): string
    {
        return $this->typo3VersionService->getPreferredVersion();
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPagerLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);

        // Check if current page is orphan - orphans should not have navigation meta links
        try {
            $currentEntry = $renderContext->getCurrentDocumentEntry();
            if ($this->isOrphanDocument($renderContext, $currentEntry)) {
                // Only return top link for orphan pages
                $documentEntries = [
                    'top' => $this->getTopDocumentEntry($renderContext),
                ];
                return $this->getPageLinks($documentEntries, $renderContext);
            }
        } catch (\Exception) {
            // Continue with normal flow
        }

        try {
            $prevEntry = $this->getPrevDocumentEntry($renderContext);
            $nextEntry = $this->getNextDocumentEntry($renderContext);

            // Maintain original order: prev, next, top
            $documentEntries = [];

            // Only include prev link if target is not an orphan
            if ($prevEntry !== null && !$this->isOrphanDocument($renderContext, $prevEntry)) {
                $documentEntries['prev'] = $prevEntry;
            }

            // Only include next link if target is not an orphan
            if ($nextEntry !== null && !$this->isOrphanDocument($renderContext, $nextEntry)) {
                $documentEntries['next'] = $nextEntry;
            }

            $documentEntries['top'] = $this->getTopDocumentEntry($renderContext);

            return $this->getPageLinks($documentEntries, $renderContext);
        } catch (\Exception) {
            $documentEntries = [
                'top' => $this->getTopDocumentEntry($renderContext),
            ];
            return $this->getPageLinks($documentEntries, $renderContext);
        }
    }

    /**
     * Returns the singlehtml link for the current version.
     *
     * @param array{env: RenderContext} $context
     * @return string|null
     */
    public function getSingleHtmlLink(array $context): ?string
    {
        $renderContext = $context['env'] ?? null;
        if (!$renderContext instanceof RenderContext) {
            return null;
        }

        try {
            $topDocument = $this->getTopDocumentEntry($renderContext);

            // Use canonical URL generator for top document
            $url = $this->urlGenerator->generateCanonicalOutputUrl($renderContext, $topDocument->getFile());

            if ($url === '#') {
                return 'singlehtml/Index.html';
            }

            // Replace per-page Index.html with singlehtml entry point
            return preg_replace('#/Index\.html$#i', '/singlehtml/Index.html', $url);

        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return PageLinkNode|null
     */
    public function getTopPageLink(array $context): ?PageLinkNode
    {
        $renderContext = $context['env'] ?? null;
        if (!$renderContext instanceof RenderContext) {
            return null;
        }

        try {
            $topEntry = $this->getTopDocumentEntry($renderContext);

            return new PageLinkNode(
                $this->urlGenerator->generateCanonicalOutputUrl($renderContext, $topEntry->getFile()),
                $topEntry->getTitle()->toString(),
                'top'
            );
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPrevNextLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);

        // Orphan pages should not display prev/next navigation links
        try {
            $currentEntry = $renderContext->getCurrentDocumentEntry();
            if ($this->isOrphanDocument($renderContext, $currentEntry)) {
                return [];
            }
        } catch (\Exception) {
            // If we can't determine current document, continue with normal flow
        }

        try {
            $prevEntry = $this->getPrevDocumentEntry($renderContext);
            $nextEntry = $this->getNextDocumentEntry($renderContext);

            $documentEntries = [];

            // Only include prev link if target is not an orphan
            if ($prevEntry !== null && !$this->isOrphanDocument($renderContext, $prevEntry)) {
                $documentEntries['prev'] = $prevEntry;
            }

            // Only include next link if target is not an orphan
            if ($nextEntry !== null && !$this->isOrphanDocument($renderContext, $nextEntry)) {
                $documentEntries['next'] = $nextEntry;
            }

            return $this->getPageLinks($documentEntries, $renderContext);
        } catch (\Exception) {
            return [];
        }
    }

    private function getNextDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        return $renderContext->getIterator()->nextNode()?->getDocumentEntry();
    }

    private function getPrevDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        return $renderContext->getIterator()->previousNode()?->getDocumentEntry();
    }

    private function getTopDocumentEntry(RenderContext $renderContext): DocumentEntryNode
    {
        return $renderContext->getProjectNode()->getRootDocumentEntry();
    }

    /**
     * Check if a document entry corresponds to an orphan page.
     * Orphan pages are marked with the :orphan: directive and should not
     * appear in navigation links (prev/next).
     */
    private function isOrphanDocument(RenderContext $renderContext, ?DocumentEntryNode $documentEntry): bool
    {
        if ($documentEntry === null) {
            return false;
        }

        try {
            $document = $renderContext->getDocumentNodeForEntry($documentEntry);
            foreach ($document->getHeaderNodes() as $headerNode) {
                if ($headerNode instanceof OrphanNode) {
                    return true;
                }
            }
        } catch (\Exception) {
            return false;
        }

        return false;
    }

    /** @param array{env: RenderContext} $context */
    private function getRenderContext(array $context): RenderContext
    {
        $renderContext = $context['env'] ?? null;
        if (!$renderContext instanceof RenderContext) {
            throw new RuntimeException('Render context must be set in the twig global state to render nodes');
        }

        return $renderContext;
    }

    /**
     * @param array<string, DocumentEntryNode|null> $documentEntries
     * @param RenderContext $renderContext
     * @return list<PageLinkNode>
     */
    public function getPageLinks(array $documentEntries, RenderContext $renderContext): array
    {
        $pagerList = [];
        foreach ($documentEntries as $rel => $documentEntry) {
            if ($documentEntry instanceof DocumentEntryNode) {
                $pagerList[] = new PageLinkNode(
                    $this->urlGenerator->generateCanonicalOutputUrl($renderContext, $documentEntry->getFile()),
                    $documentEntry->getTitle()->toString(),
                    $rel,
                );
            }
        }
        return $pagerList;
    }

    public function isRenderedForDeployment(): bool
    {
        return $this->deploymentMode->isForDeployment();
    }
    /**
     * @param array{env: RenderContext} $context
     */
    public function setBackAnchor(array $context, string $value): void
    {
        ContextRegistry::set('backAnchor', $value);
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getBackAnchor(array $context): ?string
    {
        return ContextRegistry::get('backAnchor');
    }
}
