<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\RestructuredText\Parser\Directive;

/**
 * The options of the typo3:api-class directive. They are the settings of
 * the createPhpClassDocs action of t3docs/codesnippet, with the defaults
 * that its output has.
 */
final class ApiClassSettings
{
    /** @var list<string> */
    public readonly array $members;

    /** @var list<string> */
    public readonly array $visibility;

    public readonly bool $includeInternal;
    public readonly bool $excludeDeprecated;
    public readonly bool $includeConstructor;
    public readonly bool $includeClassComment;
    public readonly bool $includeMemberComment;
    public readonly bool $includeMethodParameters;
    public readonly bool $noindex;
    public readonly bool $noindexMembers;

    public function __construct(Directive $directive)
    {
        $this->members = $this->listOption($directive, 'members');
        $this->visibility = $this->listOption($directive, 'visibility') ?: ['public'];
        $this->includeInternal = $directive->hasOption('include-internal');
        $this->excludeDeprecated = $directive->hasOption('exclude-deprecated');
        $this->includeConstructor = $directive->hasOption('include-constructor');
        $this->includeClassComment = !$directive->hasOption('no-class-comment');
        $this->includeMemberComment = !$directive->hasOption('no-member-comment');
        $this->includeMethodParameters = !$directive->hasOption('no-parameters');
        $this->noindex = $directive->hasOption('noindex');
        $this->noindexMembers = $directive->hasOption('noindex-members');
    }

    /** @return list<string> */
    private function listOption(Directive $directive, string $name): array
    {
        if (!$directive->hasOption($name)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(string $item): string => trim($item, " \t\n()$"),
            explode(',', $directive->getOptionString($name)),
        ), static fn(string $item): bool => $item !== ''));
    }
}
