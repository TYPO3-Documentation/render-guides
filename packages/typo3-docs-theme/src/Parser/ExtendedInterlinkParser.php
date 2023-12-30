<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Parser;

use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkData;
use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkParser;

use function preg_match;

final class ExtendedInterlinkParser implements InterlinkParser
{
    /** @see https://regex101.com/r/1UwRKT/1 */
    private const INTERLINK_REGEX = '/^([a-zA-Z0-9\-_\/.]+):(.*)$/';

    public function extractInterlink(string $fullReference): InterlinkData
    {
        if (!preg_match(self::INTERLINK_REGEX, $fullReference, $matches)) {
            return new InterlinkData($fullReference, '');
        }

        return new InterlinkData($matches[2], $matches[1] ?? '');
    }
}
