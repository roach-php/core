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
use Exception;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Middleware\DropRequestException;
use Sassnowski\Roach\Http\Middleware\HandlerInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Middleware\RequestMiddlewareInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @group http
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
        $request = $this->createRequest();
        $finally = $this->finallyCallback(static function (Request $request) {
            return $request->withGuzzleRequest(static function (GuzzleRequest $guzzleRequest) {
                return $guzzleRequest->withUri(new Uri('::other-url::'));
            });
        });

        $result = $stack->dispatchRequest($request, $finally)->wait();

        self::assertSame('::other-url::', (string) $result->getUri());
    }

    public function testCallMiddlewareInCorrectOrder(): void
    {
        $middlewareA = $this->makeMiddleware(static function (Request $request, HandlerInterface $next) {
            return $next($request->withGuzzleRequest(static function (GuzzleRequest $guzzleRequest) {
                return $guzzleRequest->withUri(new Uri($guzzleRequest->getUri() . 'A'));
            }));
        });
        $middlewareB = $this->makeMiddleware(static function (Request $request, HandlerInterface $next) {
            return $next($request->withGuzzleRequest(static function (GuzzleRequest $guzzleRequest) {
                return $guzzleRequest->withUri(new Uri($guzzleRequest->getUri() . 'B'));
            }));
        });
        $stack = MiddlewareStack::create($middlewareA, $middlewareB);

        $result = $stack->dispatchRequest(
            $this->createRequest(),
            $this->finallyCallback(),
        )->wait();

        self::assertSame('::url::AB', (string) $result->getUri());
    }

    public function testReturnNullWhenRequestWasDropped(): void
    {
        $dropRequestMiddleware = $this->makeMiddleware(static fn ($request) => throw new DropRequestException($request));
        $stack = MiddlewareStack::create($dropRequestMiddleware);
        $result = $stack->dispatchRequest(
            $this->createRequest(),
            $this->finallyCallback(),
        );

        self::assertNull($result);
    }

    public function testRejectPromiseIfAnUnexpectedExceptionIsThrown(): void
    {
        $request = $this->createRequest();
        $exceptionMiddleware = $this->makeMiddleware(static fn () => throw new Exception('boom'));
        $stack = MiddlewareStack::create($exceptionMiddleware);

        $promise = $stack->dispatchRequest($request, $this->finallyCallback());
        $promise->wait(false);

        self::assertSame(PromiseInterface::REJECTED, $promise->getState());
    }

    private function makeMiddleware(Closure $handle): RequestMiddlewareInterface
    {
        return new class($handle) extends RequestMiddleware {
            public function __construct(private Closure $callback)
            {
                parent::__construct();
            }

            public function handle(Request $request, HandlerInterface $next): PromiseInterface
            {
                return ($this->callback)($request, $next);
            }
        };
    }

    private function finallyCallback(?Closure $callback = null): Closure
    {
        $callback ??= static fn (Request $response) => $response;

        return static function (...$args) use ($callback) {
            $promise = new Promise(static function () use (&$promise, $args, $callback): void {
                $promise->resolve($callback(...$args));
            });

            return $promise;
        };
    }
}
