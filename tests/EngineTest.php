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

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Engine;
use Sassnowski\Roach\Http\Client;
use Sassnowski\Roach\Http\Middleware\HandlerInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\Pipeline;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Spider\ParseResult;
use Sassnowski\Roach\Testing\FakeLogger;

/**
 * @internal
 * @group integration
 */
final class EngineTest extends IntegrationTest
{
    use InteractsWithRequests;

    private FakeLogger $logger;

    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new FakeLogger();
        $this->engine = new Engine(
            new ArrayRequestQueue(),
            new Client(),
            $this->logger,
        );

        $_SERVER['__parse.called'] = 0;
    }

    public function testCrawlsStartUrls(): void
    {
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1'),
            $this->createRequest('http://localhost:8000/test2'),
        ];

        $this->engine->start($startRequests);

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
        $this->engine->start([
            $this->createRequest('http://localhost:8000/test2', $parseFunction),
        ]);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test3', 1);
    }

    public function testDontDispatchRequestsDroppedByMiddleware(): void
    {
        $middleware = new class() extends RequestMiddleware {
            public function handle(Request $request, HandlerInterface $next): PromiseInterface
            {
                if ($request->getPath() === '/test2') {
                    $this->dropRequest($request);
                }

                return $next($request);
            }
        };
        $this->engine->start([
            $this->createRequest('http://localhost:8000/test1'),
            $this->createRequest('http://localhost:8000/test2'),
        ], MiddlewareStack::create($middleware));

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

        $this->engine->start([
            $this->createRequest('http://localhost:8000/test1', $parseCallback),
        ]);

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

        $this->engine->start(
            $startRequests,
            MiddlewareStack::create(),
            new Pipeline([$processor]),
        );

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

        $this->engine->start([
            $this->createRequest('http://localhost:8000/test1', $parseCallback),
        ], MiddlewareStack::create(), new Pipeline([$processor]));

        self::assertSame(['title' => '::title::'], $processor->item);
        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testLogErrorIfExceptionOccursWhenParsingResponse(): void
    {
        $startRequests = [
            $this->createRequest(
                'http://localhost:8000/test1',
                static fn () => throw new Exception('boom'),
            ),
        ];

        $this->engine->start($startRequests);

        self::assertTrue(
            $this->logger->messageWasLogged('error', '[Engine] Error while processing response'),
        );
    }

    public function testLogErrorIfAnErrorOccursInsideRequestMiddleware(): void
    {
        $exceptionMiddleware = new class() extends RequestMiddleware {
            public function handle(Request $request, HandlerInterface $next): PromiseInterface
            {
                throw new Exception('boom');
            }
        };

        $this->engine->start([
            $this->createRequest('http://localhost:8000/test1'),
        ], MiddlewareStack::create($exceptionMiddleware));

        self::assertTrue(
            $this->logger->messageWasLogged('error', '[Engine] Error while dispatching request'),
        );
    }
}
