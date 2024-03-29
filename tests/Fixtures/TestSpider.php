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

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

final class TestSpider extends BasicSpider
{
    public function parse(Response $response): \Generator
    {
        yield from [];
    }
}
