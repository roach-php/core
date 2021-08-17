<?php

use Ergebnis\PhpCsFixer\Config;

$header = <<<EOF
Copyright (c) 2021 Kai Sassnowski

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.

@see https://github.com/roach-php/roach
EOF;

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php80($header));

$config->getFinder()->in(__DIR__);
$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/.php-cs-fixer.cache');

return $config;