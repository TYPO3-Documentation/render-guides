<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\RestructuredText\TextRoles\AbstractReferenceTextRole;
use Psr\Log\LoggerInterface;

class IssueReferenceTextRole extends AbstractReferenceTextRole
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
        parent::__construct($this->logger);
    }

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
        return new HyperLinkNode($referenceName ?? sprintf('forge#%s', (int)$referenceTarget), sprintf('https://forge.typo3.org/issues/%s', (int)$referenceTarget));
    }
}
