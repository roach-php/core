<?php

declare(strict_types=1);

namespace RoachPHP\Tests\Fixtures;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

/**
 * @internal
 */
final class TestSpider2 extends BasicSpider
{
    public function parse(Response $response): Generator
    {
        yield from [];
    }
}