<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Tests;

use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Container\SimpleContainer;
use Sassnowski\Roach\Engine;
use Sassnowski\Roach\Http\Middleware\Handler;
use Sassnowski\Roach\Http\Middleware\RequestMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Spider\AbstractSpider;
use Sassnowski\Roach\Spider\ParseResult;
use Sassnowski\Roach\Tests\TestClasses\SpiderBuilder;

/**
 * @covers \Sassnowski\Roach\Engine
 *
 * @internal
 */
final class EngineTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['__parse.called'] = 0;
        $_SERVER['__processor.called'] = 0;
    }

    public function testCrawlsStartUrls(): void
    {
        $spider = SpiderBuilder::new()
            ->withStartUrls(
                'http://localhost:8000/test1',
                'http://localhost:8000/test2',
            )->build();

        $this->startSpider($spider);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testCrawlUrlsReturnedFromParseCallback(): void
    {
        $spider = SpiderBuilder::new()
            ->withStartUrls('http://localhost:8000/test2')
            ->parseResponse(static function (Response $response, AbstractSpider $spider) {
                foreach ($response->filter('a')->links() as $link) {
                    yield ParseResult::request($link->getUri(), [$spider, 'parse']);
                }
            })
            ->build();

        $this->startSpider($spider);

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
        $spider = SpiderBuilder::new()
            ->withStartUrls(
                'http://localhost:8000/test1',
                'http://localhost:8000/test2',
            )
            ->withMiddleware($middleware::class)
            ->build();

        $this->startSpider($spider);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasNotCrawled('/test2');
    }

    public function testCallCorrectParseCallbackForRequest(): void
    {
        $spider = SpiderBuilder::new()
            ->parseResponse(static function (Response $response, AbstractSpider $spider) {
                yield ParseResult::request('http://localhost:8000/test2', static function (Response $response): void {
                    ++$_SERVER['__parse.called'];
                });
            })
            ->withStartUrls('http://localhost:8000/test1')
            ->build();

        $this->startSpider($spider);

        self::assertEquals(1, $_SERVER['__parse.called']);
    }

    public function testSendItemsThroughItemPipeline(): void
    {
        $processor = new class() {
            public mixed $item = null;

            public function processItem(mixed $item)
            {
                ++$_SERVER['__processor.called'];

                return $item;
            }
        };
        $spider = SpiderBuilder::new()
            ->withStartUrls('http://localhost:8000/test1')
            ->withItemProcessors($processor::class)
            ->parseResponse(static function (Response $response, AbstractSpider $spider) {
                $title = $response->filter('h1#headline')->text();

                yield ParseResult::item([
                    'title' => $title,
                ]);
            })
            ->build();

        $this->startSpider($spider);

        self::assertEquals(1, $_SERVER['__processor.called']);
    }

    public function testHandleBothRequestAndItemEmittedFromSameParseCallback(): void
    {
        $processor = new class() {
            public function processItem(mixed $item)
            {
                ++$_SERVER['__processor.called'];

                return $item;
            }
        };
        $spider = SpiderBuilder::new()
            ->withStartUrls('http://localhost:8000/test1')
            ->withItemProcessors($processor::class)
            ->parseResponse(static function (Response $response, AbstractSpider $spider) {
                yield ParseResult::item(['title' => '::title::']);

                yield ParseResult::request('http://localhost:8000/test2', static function (): void {
                });
            })
            ->build();

        $this->startSpider($spider);

        self::assertSame(1, $_SERVER['__processor.called']);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    private function startSpider(AbstractSpider $spider): void
    {
        (new Engine($spider, new ArrayRequestQueue(), new SimpleContainer()))->start();
    }
}
