<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages/typo3-docs-theme/src',
        __DIR__ . '/packages/typo3-guides-cli/src',
        __DIR__ . '/packages/typo3-guides-extension/src',
        __DIR__ . '/packages/typo3-version-handling/src',
        __DIR__ . '/tools',
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    ->withAttributesSets(symfony: true, phpunit: true)
    ->withRules([
        // PHP 8.3: Add #[\Override] to methods overriding parent
        \Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withSkip([
        // Skip rules that cause issues
        \Rector\Php83\Rector\ClassConst\AddTypeToConstRector::class,
        \Rector\Php84\Rector\Class_\PropertyHookRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class,
    ]);
