<?php

namespace T3Docs\Typo3DocsTheme\Api;

/** Why a file of api.typo3.org could not be loaded. */
final class FetchFailure
{
    public function __construct(
        public readonly string $message,
    ) {}
}
