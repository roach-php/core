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

namespace Sassnowski\Roach\Spider;

use Sassnowski\Roach\ResponseProcessing\ParseResult;

trait ParseResultHelpers
{
    protected function request(string $url, string $parseMethod = 'parse'): ParseResult
    {
        /** @phpstan-ignore-next-line */
        return ParseResult::request($url, [$this, $parseMethod]);
    }

    protected function item(mixed $item): ParseResult
    {
        return ParseResult::item($item);
    }
}
