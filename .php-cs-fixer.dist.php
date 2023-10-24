<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config
    ->setCacheFile('.cache/.php-cs-fixer.cache')
    ->getFinder()->in(__DIR__)
    ->exclude([
        'docs',
        'fixtures-local',
    ])
;

return $config;
