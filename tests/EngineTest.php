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

namespace Sassnowski\Roach\Tests;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Engine;
use Sassnowski\Roach\Http\Middleware\Handler;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Spider\ParseResult;

/**
 * @covers \Sassnowski\Roach\Engine
 *
 * @internal
 * @group integration
 */
final class EngineTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['__parse.called'] = 0;
    }

    public function testCrawlsStartUrls(): void
    {
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1'),
            $this->createRequest('http://localhost:8000/test2'),
        ];

        Engine::create($startRequests)->start();

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testCrawlUrlsReturnedFromParseCallback(): void
    {
        $parseFunction = static function (Response $response) {
            foreach ($response->filter('a')->links() as $link) {
                yield ParseResult::request($link->getUri(), static fn (Response $response) => yield from []);
            }
        };
        $startRequests = [
            $this->createRequest('http://localhost:8000/test2', $parseFunction),
        ];

        Engine::create($startRequests)->start();

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test3', 1);
    }

    public function testDontDispatchRequestsDroppedByMiddleware(): void
    {
        $middleware = new class() extends RequestMiddleware {
            public function handle(Request $request, Handler $next): PromiseInterface
            {
                if ($request->getUri()->getPath() === '/test2') {
                    $this->dropRequest($request);
                }

                return $next($request);
            }
        };
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1'),
            $this->createRequest('http://localhost:8000/test2'),
        ];

        Engine::create($startRequests, middleware: [$middleware])
            ->start();

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasNotCrawled('/test2');
    }

    public function testCallCorrectParseCallbackForRequest(): void
    {
        $parseCallback = static function (Response $response) {
            yield ParseResult::request('http://localhost:8000/test2', static function (Response $response): void {
                ++$_SERVER['__parse.called'];
            });
        };
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1', $parseCallback),
        ];

        Engine::create($startRequests)->start();

        self::assertEquals(1, $_SERVER['__parse.called']);
    }

    public function testSendItemsThroughItemPipeline(): void
    {
        $processor = new class() {
            public mixed $item = null;

            public function processItem(mixed $item)
            {
                $this->item = $item;

                return $item;
            }
        };
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1', static function (Response $response) {
                yield ParseResult::item([
                    'title' => $response->filter('h1#headline')->text(),
                ]);
            }),
        ];

        Engine::create($startRequests, itemProcessors: [$processor])->start();

        self::assertSame(['title' => 'Such headline, wow'], $processor->item);
    }

    public function testHandleBothRequestAndItemEmittedFromSameParseCallback(): void
    {
        $processor = new class() {
            public mixed $item = null;

            public function processItem(mixed $item)
            {
                $this->item = $item;

                return $item;
            }
        };
        $parseCallback = static function (Response $response) {
            yield ParseResult::item(['title' => '::title::']);

            yield ParseResult::request('http://localhost:8000/test2', static function (): void {
            });
        };
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1', $parseCallback),
        ];

        Engine::create($startRequests, itemProcessors: [$processor])->start();

        self::assertSame(['title' => '::title::'], $processor->item);
        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    private function createRequest(string $url, ?Closure $callback = null): Request
    {
        $callback ??= static function (Response $response): void {
        };

        return new Request($url, $callback);
    }
}
