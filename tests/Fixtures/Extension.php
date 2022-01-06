<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Support\Configurable;

final class Extension implements ExtensionInterface
{
    use Configurable;

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    private function defaultOptions(): array
    {
        return [
            '::option-key::' => '::default-option-value::',
        ];
    }
}
