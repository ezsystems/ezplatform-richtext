<?php

$header = <<<'EOF'
@copyright Copyright (C) eZ Systems AS. All rights reserved.
@license For full copyright and license information view LICENSE file distributed with this source code.
EOF;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'simplified_null_return' => false,
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false,
        'cast_spaces' => false,
        'blank_line_after_opening_tag' => false,
        'single_blank_line_before_namespace' => true,
        'space_after_semicolon' => false,
        'header_comment' => [
            'commentType' => 'PHPDoc',
            'header' => $header,
            'location' => 'after_open',
            'separate' => 'top',
        ],
        'yoda_style' => false,
        'no_break_comment' => false,
        'declare_strict_types' => true,
        'native_function_invocation' => false,
        'phpdoc_types_order' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
            ->files()->name('*.php')
    )
;
