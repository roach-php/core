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

namespace RoachPHP\Tests\Spider;

use Generator;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Roach;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Tests\IntegrationTest;

/**
 * @internal
 */
final class SpiderTest extends IntegrationTest
{
    public function testCreateInitialRequestFromStartUrlsByDefault(): void
    {
        $spider = new class() extends BasicSpider {
            public array $startUrls = [
                'http://localhost:8000/test1',
                'http://localhost:8000/test2',
            ];

            // Don't want logging in this test
            public array $extensions = [];

            public function parse(Response $response): Generator
            {
                yield from [];
            }
        };

        Roach::startSpider($spider::class);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testOverrideInitialRequests(): void
    {
        $spider = new class() extends BasicSpider {
            // Don't want logging in this test
            public array $extensions = [];

            public function parse(Response $response): Generator
            {
                yield from [];
            }

            protected function initialRequests(): array
            {
                return [new Request('GET', 'http://localhost:8000/test1', [$this, 'parse'])];
            }
        };

        Roach::startSpider($spider::class);

        $this->assertRouteWasCrawledTimes('/test1', 1);
    }
}
