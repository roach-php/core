<?php

use Ergebnis\PhpCsFixer\Config;

$header = <<<EOF
Copyright (c) 2023 Kai Sassnowski

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.

@see https://github.com/roach-php/roach
EOF;

$ruleSet =  Config\RuleSet\Php80::create()
    ->withHeader($header)
    ->withRules(Config\Rules::fromArray([
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
    ]));

$config = Config\Factory::fromRuleSet($ruleSet);

$config->getFinder()->in(__DIR__);
$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/.php-cs-fixer.cache');

return $config;