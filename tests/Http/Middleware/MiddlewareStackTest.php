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
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Middleware\Handler;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Middleware\RequestMiddlewareInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @covers \Sassnowski\Roach\Http\Middleware\MiddlewareStack
 * @group middleware
 *
 * @internal
 */
final class MiddlewareStackTest extends TestCase
{
    use InteractsWithRequests;

    public function testCanBeCreatedFromEmptyArray(): void
    {
        $stack = MiddlewareStack::create();
        $request = $this->createRequest('::url::');
        $finally = $this->wrapInPromise(
            static fn (Request $request) => $request->withUri(new Uri('::other-url::')),
        );

        $result = $stack->dispatchRequest($request, $finally)->wait();

        self::assertSame('::other-url::', (string) $result->getUri());
    }

    public function testCallMiddlewareInCorrectOrder(): void
    {
        $request = $this->createRequest('::url::');
        $stack = MiddlewareStack::create(new MiddlewareA(), new MiddlewareB());
        $finally = $this->wrapInPromise(static fn (Request $request) => $request);

        $result = $stack->dispatchRequest($request, $finally)->wait();

        self::assertSame('::url::AB', (string) $result->getUri());
    }

    public function testReturnNullWhenRequestWasDropped(): void
    {
        $request = $this->createRequest('::url::');
        $stack = MiddlewareStack::create(new DropRequestMiddleware());
        $finally = $this->wrapInPromise(static fn (Request $request) => $request);

        $result = $stack->dispatchRequest($request, $finally);

        self::assertNull($result);
    }

    private function wrapInPromise(Closure $callback)
    {
        return static function (...$args) use ($callback) {
            $promise = new Promise(static function () use (&$promise, $args, $callback): void {
                $promise->resolve($callback(...$args));
            });

            return $promise;
        };
    }
}

final class MiddlewareA implements RequestMiddlewareInterface
{
    public function handle(Request $request, Handler $next): PromiseInterface
    {
        return $next($request->withUri(new Uri($request->getUri() . 'A')));
    }
}

final class MiddlewareB implements RequestMiddlewareInterface
{
    public function handle(Request $request, Handler $next): PromiseInterface
    {
        return $next($request->withUri(new Uri($request->getUri() . 'B')));
    }
}

final class DropRequestMiddleware extends RequestMiddleware
{
    public function handle(Request $request, Handler $next): PromiseInterface
    {
        $this->dropRequest($request);
    }
}
