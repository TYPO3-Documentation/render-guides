<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\RestructuredText\TextRoles\AbstractReferenceTextRole;
use Psr\Log\LoggerInterface;

final class IssueReferenceTextRole extends AbstractReferenceTextRole
{
    private const string FORGE_DEFAULT_LABEL = 'forge#%d';
    private const string FORGE_ISSUE_URL = 'https://forge.typo3.org/issues/%d';
    private const string FORGE_URL = 'https://forge.typo3.org/';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    final public const string NAME = 'issue';

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getAliases(): array
    {
        return [];
    }

    #[\Override]
    protected function createNode(string $referenceTarget, string|null $referenceName, string $role): \phpDocumentor\Guides\Nodes\Inline\HyperLinkNode
    {
        if ((int)$referenceTarget <= 0) {
            $this->logger->warning(sprintf('Expected a positive integer as issue number. Found %s', $referenceTarget));
            return new HyperLinkNode($referenceName ?? 'Forge', self::FORGE_URL);
        }
        return new HyperLinkNode($referenceName ?? sprintf(self::FORGE_DEFAULT_LABEL, $referenceTarget), sprintf(self::FORGE_ISSUE_URL, $referenceTarget));
    }
}
