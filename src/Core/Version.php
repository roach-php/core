<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Core;

final class Version
{
    private static string $version = '1.0.0';

    public static function id(): string
    {
        return self::$version;
    }

    public static function getVersionString(): string
    {
        return 'Roach PHP ' . self::id() . ' by Kai Sassnowski and contributors.';
    }
}
