<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config
    ->getFinder()->in(__DIR__)
    ->exclude([
        'docs',
        'fixtures-local',
    ])
;

return $config;
