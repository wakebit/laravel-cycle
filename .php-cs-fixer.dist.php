<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->in(__DIR__)
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'               => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align',
            ],
        ],
        'braces' => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure'                         => false,
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'ordered_imports'        => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],
        'phpdoc_to_comment'      => false,
        'single_line_throw'      => false,
        'yoda_style'             => [
            'equal'            => false,
            'identical'        => false,
            'less_and_greater' => false,
        ],
    ])
    ->setFinder($finder);
