<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ConfvalVersions;

use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogReferences;

use function preg_match;
use function sprintf;
use function trim;

/**
 * When an option was added, changed, deprecated or removed, from the options
 * of its ".. confval::".
 *
 * Each is a version, optionally followed by the changelog entry that says
 * more, in any form a version directive's ":changelog:" takes:
 *
 *     :removed: 14.0 breaking-106863-1749629371
 *
 * They replace a version directive inside the confval: a version the option
 * carries is one the index can list, where one in the description is text.
 *
 * @phpstan-type Version array{kind: string, label: string, version: string, changelog: string, reference: ReferenceNode|null}
 */
final class ConfvalVersions
{
    /** The option, and how the page names it. */
    public const OPTIONS = [
        'added' => 'Added',
        'changed' => 'Changed',
        'deprecated' => 'Deprecated',
        'removed' => 'Removed',
    ];

    /** A version, and what follows it. */
    private const VALUE = '/^(\d+(?:\.\d+)*(?:\.x)?)(?:\s+(.*))?$/s';

    /**
     * What was read, by the anchor of the confval: the page and each index
     * ask for it, and a value is reported only once.
     *
     * @var array<string, list<Version>>
     */
    private array $read = [];

    public function __construct(
        private readonly ChangelogReferences $changelogReferences,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $loggerInformation
     * @return list<Version>
     */
    public function of(ConfvalNode $node, array $loggerInformation = []): array
    {
        return $this->read[$node->getAnchor()] ??= $this->read($node, $loggerInformation);
    }

    /**
     * @param array<string, mixed> $loggerInformation
     * @return list<Version>
     */
    private function read(ConfvalNode $node, array $loggerInformation): array
    {
        $versions = [];
        foreach (self::OPTIONS as $option => $label) {
            $value = $node->getAdditionalOptions()[$option] ?? null;
            if ($value === null) {
                continue;
            }

            $text = trim($value->toString());
            if (preg_match(self::VALUE, $text, $matches) !== 1) {
                $this->logger->warning(sprintf(
                    'The ":%s: %s" option of the confval "%s" is not a version such as "14.0", optionally followed by a changelog entry. ',
                    $option,
                    $text,
                    $node->getPlainContent(),
                ), $loggerInformation);
                continue;
            }

            $changelog = trim($matches[2] ?? '');
            $versions[] = [
                'kind' => $option,
                'label' => $label,
                'version' => $matches[1],
                'changelog' => $changelog,
                'reference' => $changelog === '' ? null : $this->changelogReferences->build($changelog, $option, $loggerInformation),
            ];
        }

        return $versions;
    }

    /** Whether a field of a confval is one of these, which are not shown as fields. */
    public static function isVersionOption(string $field): bool
    {
        return isset(self::OPTIONS[$field]);
    }
}
