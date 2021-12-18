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

namespace RoachPHP\Tests;

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

        try {
            \unlink(__DIR__ . '/Server/tmp/crawled.json');
        } catch (Throwable) {
        }
    }

    protected function skipIfServerNotRunning(): void
    {
        try {
            \file_get_contents("{$this->serverUrl}/ping");
        } catch (Throwable) {
            self::markTestSkipped('Skipping integration test. Server not running.');
        }
    }

    protected function assertRouteWasCrawled(string $route): void
    {
        self::assertArrayHasKey($route, $this->getCrawledRoutes());
    }

    protected function assertRouteWasCrawledTimes(string $route, int $times): void
    {
        $crawledRoutes = $this->getCrawledRoutes();

        self::assertArrayHasKey($route, $crawledRoutes);
        self::assertSame($times, $crawledRoutes[$route]);
    }

    protected function assertRouteWasNotCrawled(string $route): void
    {
        self::assertArrayNotHasKey($route, $this->getCrawledRoutes());
    }

    private function getCrawledRoutes(): array
    {
        $response = \file_get_contents("{$this->serverUrl}/crawled-routes");

        if (!$response) {
            return [];
        }

        return \json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
