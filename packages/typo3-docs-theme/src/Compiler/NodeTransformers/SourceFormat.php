<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Settings\SettingsManager;

use function is_string;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * The format a document was written in, "rst" or "md".
 *
 * A manual can list several input formats, input-format="rst,md", so the
 * project setting does not say which one a page uses. The extension of the
 * file it was parsed from does; OriginalFileNameSetter keeps that file name
 * on the document. Without it, the format is the configured one.
 *
 * @internal
 */
final class SourceFormat
{
    public static function of(DocumentNode $document, SettingsManager $settingsManager): string
    {
        $fileName = $document->getOption('originalFileName');
        if (is_string($fileName)) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if ($extension !== '') {
                return $extension;
            }
        }

        return $settingsManager->getProjectSettings()->getInputFormat();
    }
}
