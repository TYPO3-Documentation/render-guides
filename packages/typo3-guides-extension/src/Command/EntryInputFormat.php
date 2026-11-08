<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Command;

use phpDocumentor\Guides\Settings\ProjectSettings;

/**
 * The --input-format that RunDecorator passes for the entry file it found.
 *
 * The option takes precedence over guides.xml, so it would reduce a list such
 * as input-format="rst,md" to the entry file's format and leave out every
 * page of the other. A list that contains the entry file's format is kept.
 *
 * @internal
 */
final class EntryInputFormat
{
    public static function keepConfiguredFormats(?string $entryFormat, ProjectSettings $projectSettings): ?string
    {
        // getInputFormats() and lists arrive with phpDocumentor/guides#1448.
        // After that version is required, phpstan reports this ignore as unused.
        if ($entryFormat === null || !method_exists($projectSettings, 'getInputFormats')) { // @phpstan-ignore function.impossibleType
            return $entryFormat;
        }

        /** @var list<string> $configuredFormats */
        $configuredFormats = $projectSettings->getInputFormats();
        if (count($configuredFormats) < 2 || !in_array($entryFormat, $configuredFormats, true)) {
            return $entryFormat;
        }

        return implode(',', $configuredFormats);
    }
}
