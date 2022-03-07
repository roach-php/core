<?php

use Ergebnis\PhpCsFixer\Config;
use PhpCsFixer\RuleSet\RuleSet;

$header = <<<EOF
Copyright (c) 2022 Kai Sassnowski

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.

@see https://github.com/roach-php/roach
EOF;

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php80($header), [
    'php_unit_test_class_requires_covers' => false,
    'class_attributes_separation' => [
        'elements' => [
            'const' => 'one',
            'method' => 'one',
            'property' => 'one',
            'trait_import' => 'none',
        ],
    ],
    'error_suppression' => [
        'noise_remaining_usages' => false,
    ],
]);

$config->getFinder()->in(__DIR__);
$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/.php-cs-fixer.cache');

return $config;