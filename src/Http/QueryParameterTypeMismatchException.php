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

use Exception;

final class QueryParameterTypeMismatchException extends Exception
{
    public static function forInt(string $key): self
    {
        return new self("Unable to cast non-numeric parameter [{$key}] to an integer");
    }

    public static function forFloat(string $key): self
    {
        return new self("Unable to cast non-numeric parameter [{$key}] to a float");
    }

    public static function forArray(string $key): self
    {
        return new self("Parameter [{$key}] is not an array");
    }
}
