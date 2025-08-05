<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Http;

final class MalformedUriException extends \Exception
{
    public static function forUri(string $uri): self
    {
        return new self("Unable to parse URI [{$uri}]");
    }
}
