<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\Typo3DocsTheme\TextRoles\PhpTextRole;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * "phpApiClass(fqn)" returns the node that the :php: role makes of a class
 * of the TYPO3 API, so that a template shows it with the same information
 * and link to api.typo3.org as the role does. For another class it returns
 * null: the role could only suggest to search for it.
 */
final class PhpCodeExtension extends AbstractExtension
{
    public function __construct(
        private readonly PhpTextRole $phpTextRole,
        private readonly Typo3ApiService $typo3ApiService,
    ) {}

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('phpApiClass', $this->apiClass(...)),
        ];
    }

    private function apiClass(string $fqn): ?InlineNode
    {
        $fqn = '\\' . ltrim($fqn, '\\');
        if ($this->typo3ApiService->getClassInfo($fqn) === []) {
            return null;
        }

        return $this->phpTextRole->createNode('php', $fqn);
    }
}
