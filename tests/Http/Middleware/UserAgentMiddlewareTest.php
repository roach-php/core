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

namespace Sassnowski\Roach\Tests\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Middleware\SetUserAgentMiddleware;
use Sassnowski\Roach\Testing\FakeHandler;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @internal
 * @group middleware
 */
final class UserAgentMiddlewareTest extends TestCase
{
    use InteractsWithRequests;

    private FakeHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new FakeHandler();
    }

    public function testSetDefaultUserAgentOnRequest(): void
    {
        $middleware = new SetUserAgentMiddleware();

        $response = $middleware->handle($this->createRequest(), $this->handler)->wait();

        $request = $response->getRequest();
        self::assertTrue($request->hasHeader('User-Agent'));
        self::assertSame('roach-php', $request->getHeaderLine('User-Agent'));
    }

    public function testSetCustomUserAgentOnRequest(): void
    {
        $middleware = new SetUserAgentMiddleware('custom');

        $response = $middleware->handle($this->createRequest(), $this->handler)->wait();

        $request = $response->getRequest();
        self::assertTrue($request->hasHeader('User-Agent'));
        self::assertSame('custom', $request->getHeaderLine('User-Agent'));
    }
}
