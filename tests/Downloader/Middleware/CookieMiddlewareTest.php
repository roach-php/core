<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Downloader\Middleware;

use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\CookieMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class CookieMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testAddCookieJarToRequestOptions(): void
    {
        $jar = new CookieJar();
        $middleware = new CookieMiddleware($jar);
        $request = $this->makeRequest();

        $processedRequest = $middleware->handleRequest($request);

        self::assertSame($jar, $processedRequest->getOptions()['cookies']);
    }
}
