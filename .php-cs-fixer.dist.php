<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'align_multiline_comment' => true,
        'single_quote' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'no_trailing_whitespace' => true,
        'no_extra_blank_lines' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
    );
