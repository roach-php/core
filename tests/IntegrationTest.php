<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
abstract class IntegrationTest extends TestCase
{
    protected string $serverUrl = 'http://localhost:8000';

    protected function setUp(): void
    {
        $this->skipIfServerNotRunning();
        \unlink(__DIR__ . '/Server/tmp/crawled.json');
    }

    protected function skipIfServerNotRunning(): void
    {
        try {
            \file_get_contents("{$this->serverUrl}/ping");
        } catch (Throwable) {
            self::markTestSkipped('Skipping integration test. Server not running.');
        }
    }

    protected function assertRouteWasCrawledTimes(string $route, int $times): void
    {
        $crawledRoutes = \json_decode(
            \file_get_contents("{$this->serverUrl}/crawled-routes"),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertArrayHasKey($route, $crawledRoutes);
        self::assertSame($times, $crawledRoutes[$route]);
    }

    protected function assertRouteWasNotCrawled(string $route): void
    {
        $crawledRoutes = \json_decode(
            \file_get_contents("{$this->serverUrl}/crawled-routes"),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertArrayNotHasKey($route, $crawledRoutes);
    }
}
