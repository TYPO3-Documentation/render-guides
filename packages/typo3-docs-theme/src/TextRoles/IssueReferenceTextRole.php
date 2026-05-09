<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\RestructuredText\TextRoles\AbstractReferenceTextRole;
use Psr\Log\LoggerInterface;

final class IssueReferenceTextRole extends AbstractReferenceTextRole
{
    private const FORGE_DEFAULT_LABEL = 'forge#%d';
    private const FORGE_ISSUE_URL = 'https://forge.typo3.org/issues/%d';
    private const FORGE_URL = 'https://forge.typo3.org/';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    final public const NAME = 'issue';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @inheritDoc */
    public function getAliases(): array
    {
        return [];
    }

    /** @return HyperLinkNode */
    protected function createNode(string $referenceTarget, string|null $referenceName, string $role): AbstractLinkInlineNode
    {
        if ((int)$referenceTarget <= 0) {
            $this->logger->warning(sprintf('Expected a positive integer as issue number. Found %s', $referenceTarget));
            $label = $referenceName ?? 'Forge';
            return new HyperLinkNode($label === '' ? [] : [new PlainTextInlineNode($label)], self::FORGE_URL);
        }
        $label = $referenceName ?? sprintf(self::FORGE_DEFAULT_LABEL, $referenceTarget);
        return new HyperLinkNode($label === '' ? [] : [new PlainTextInlineNode($label)], sprintf(self::FORGE_ISSUE_URL, $referenceTarget));
    }
}
