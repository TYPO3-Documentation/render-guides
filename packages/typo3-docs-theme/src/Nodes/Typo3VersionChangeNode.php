<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;

use function sprintf;

/**
 * Variant of phpDocumentor's VersionChangeNode that can additionally carry a
 * reference to a TYPO3 changelog entry, set via the ":changelog:" option of the
 * versionadded, versionchanged and deprecated directives. The reference is
 * resolved against the changelog inventory during rendering.
 *
 * @extends CompoundNode<Node>
 */
final class Typo3VersionChangeNode extends CompoundNode
{
    /** Shown as the link text, and as the reference's value in a warning when the entry is missing. */
    public const CHANGELOG_LINK_TEXT = 'See changelog entry';

    private readonly string $versionLabel;

    /** @param list<Node> $value */
    public function __construct(
        private readonly string $type,
        string $versionLabel,
        private readonly string $versionModified,
        array $value,
        private readonly ReferenceNode|null $changelogReference = null,
    ) {
        parent::__construct($value);

        $this->versionLabel = sprintf($versionLabel, $versionModified);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVersionLabel(): string
    {
        return $this->versionLabel;
    }

    public function getVersionModified(): string
    {
        return $this->versionModified;
    }

    public function getChangelogReference(): ReferenceNode|null
    {
        return $this->changelogReference;
    }

    public function getChangelogLinkText(): string
    {
        return self::CHANGELOG_LINK_TEXT;
    }
}
