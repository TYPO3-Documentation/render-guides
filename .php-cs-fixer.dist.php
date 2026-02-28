<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/packages')
    ->in(__DIR__.'/tests')
    ->exclude([
        'docs',
        'fixtures-local',
    ]);

return (new PhpCsFixer\Config())
    ->setCacheFile('.cache/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS1.0' => true,
        '@PHP82Migration' => true,

        // Already implemented PER-CS2 rules we opt-in explicitly
        // @todo: Can be dropped once we enable @PER-CS2.0
        'concat_space' => [
            'spacing' => 'one'
        ],
        'function_declaration' => [
            'closure_fn_spacing' => 'none',
        ],
        'method_argument_space' => true,
        'single_line_empty_body' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
