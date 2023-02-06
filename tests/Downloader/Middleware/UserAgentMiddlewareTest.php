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

namespace RoachPHP\Tests\Downloader\Middleware;

use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\UserAgentMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @group downloader
 * @group middleware
 *
 * @internal
 */
final class UserAgentMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testSetDefaultUserAgentOnRequest(): void
    {
        $middleware = new UserAgentMiddleware();
        $middleware->configure([]);

        $request = $middleware->handleRequest($this->makeRequest());

        self::assertTrue($request->hasHeader('User-Agent'));
        self::assertSame('roach-php', $request->getHeader('User-Agent')[0]);
    }

    public function testSetCustomUserAgentOnRequest(): void
    {
        $middleware = new UserAgentMiddleware();
        $middleware->configure(['userAgent' => 'custom']);

        $request = $middleware->handleRequest($this->makeRequest());

        self::assertTrue($request->hasHeader('User-Agent'));
        self::assertSame('custom', $request->getHeader('User-Agent')[0]);
    }
}
