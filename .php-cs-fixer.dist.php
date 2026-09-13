<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/packages')
    ->in(__DIR__.'/tests')
    // Whatever is generated is ignored by git, and nothing generated is ours to
    // format. Reading the rules instead of listing the directories keeps this
    // right when the next generated directory appears: "make test-theme-js"
    // installs a PHP file below node_modules, and the rendered output of the
    // integration tests lands in their temp directories.
    ->ignoreVCSIgnored(true)
    // Also excluded, because exclude() prunes the directory rather than
    // filtering its files one by one.
    ->exclude([
        'docs',
        'fixtures-local',
        'node_modules',
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
