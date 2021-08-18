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

use Closure;
use GuzzleHttp\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Middleware\DropRequestException;
use Sassnowski\Roach\Http\Middleware\Handler;
use Sassnowski\Roach\Http\Middleware\RequestDeduplicationMiddleware;
use Sassnowski\Roach\Logging\FakeLogger;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @group middleware
 *
 * @internal
 */
final class RequestDeduplicationMiddlewareTest extends TestCase
{
    use InteractsWithRequests;

    private RequestDeduplicationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RequestDeduplicationMiddleware();
    }

    public function testDropsRequestIfItWasAlreadySeenBefore(): void
    {
        $request = $this->createRequest();
        $handler = $this->makeHandler();

        $this->middleware->handle($request, $handler);

        $this->expectException(DropRequestException::class);
        $this->middleware->handle($request, $handler);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDoesNothingIfUrlWasNotSeenBefore(): void
    {
        $requestA = $this->createRequest('::url-a::');
        $requestB = $this->createRequest('::url-b::');
        $handler = $this->makeHandler();

        $this->middleware->handle($requestA, $handler);
        $this->middleware->handle($requestB, $handler);
    }

    public function testLogDroppedRequests(): void
    {
        $logger = new FakeLogger();
        $middleware = new RequestDeduplicationMiddleware($logger);
        $request = $this->createRequest();
        $handler = $this->makeHandler();

        $middleware->handle($request, $handler);

        $this->expectException(DropRequestException::class);
        $middleware->handle($request, $handler);

        self::assertTrue(
            $logger->messageWasLogged('info', 'Dropping duplicate request', ['uri' => '::url::']),
        );
    }

    private function makeHandler(?Closure $callback = null): Handler
    {
        return new Handler($callback ?: static function () {
            return new Promise();
        });
    }
}
