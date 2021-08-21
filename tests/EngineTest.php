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
use Sassnowski\Roach\Core\Engine;
use Sassnowski\Roach\Core\Run;
use Sassnowski\Roach\Http\Client;
use Sassnowski\Roach\Http\Middleware\HandlerInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack as HttpMiddleware;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\Parsing\MiddlewareStack as ResponseMiddleware;
use Sassnowski\Roach\Parsing\ParseResult;
use Sassnowski\Roach\Scheduling\ArrayRequestScheduler;
use Sassnowski\Roach\Scheduling\Timing\FakeClock;
use Sassnowski\Roach\Testing\FakeLogger;
use Sassnowski\Roach\Testing\FakeProcessor;

/**
 * @internal
 * @group integration
 */
final class EngineTest extends IntegrationTest
{
    use InteractsWithRequests;
    use InteractsWithPipelines;

    private FakeLogger $logger;

    private Engine $engine;

    private ImmutableItemPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new FakeLogger();
        $this->pipeline = new ImmutableItemPipeline($this->logger);
        $this->engine = new Engine(
            new ArrayRequestScheduler(new FakeClock()),
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
        $run = new Run(
            $startRequests,
            HttpMiddleware::create(),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

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
        $run = new Run(
            [$this->createRequest('http://localhost:8000/test2', $parseFunction)],
            HttpMiddleware::create(),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

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
        $run = new Run(
            [
                $this->createRequest('http://localhost:8000/test1'),
                $this->createRequest('http://localhost:8000/test2'),
            ],
            HttpMiddleware::create($middleware),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

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
        $run = new Run(
            [$this->createRequest('http://localhost:8000/test1', $parseCallback)],
            HttpMiddleware::create(),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        self::assertEquals(1, $_SERVER['__parse.called']);
    }

    public function testSendItemsThroughItemPipeline(): void
    {
        $processor = new FakeProcessor();
        $startRequests = [
            $this->createRequest('http://localhost:8000/test1', static function (Response $response) {
                yield ParseResult::item([
                    'title' => $response->filter('h1#headline')->text(),
                ]);
            }),
        ];
        $run = new Run(
            $startRequests,
            HttpMiddleware::create(),
            $this->pipeline->setProcessors($processor),
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => 'Such headline, wow']));
    }

    public function testHandleBothRequestAndItemEmittedFromSameParseCallback(): void
    {
        $processor = new FakeProcessor();
        $parseCallback = static function (Response $response) {
            yield ParseResult::item(['title' => '::title::']);

            yield ParseResult::request('http://localhost:8000/test2', static function (): void {
            });
        };
        $run = new Run(
            [$this->createRequest('http://localhost:8000/test1', $parseCallback)],
            HttpMiddleware::create(),
            $this->pipeline->setProcessors($processor),
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => '::title::']));
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
        $run = new Run(
            $startRequests,
            HttpMiddleware::create(),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

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
        $run = new Run(
            [$this->createRequest('http://localhost:8000/test1')],
            HttpMiddleware::create($exceptionMiddleware),
            $this->pipeline,
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        self::assertTrue(
            $this->logger->messageWasLogged('error', '[Engine] Error while dispatching request'),
        );
    }
}
