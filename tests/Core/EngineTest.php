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

namespace RoachPHP\Tests\Core;

use RoachPHP\Core\Engine;
use RoachPHP\Core\Run;
use RoachPHP\Downloader\Downloader;
use RoachPHP\Downloader\Middleware\DownloaderMiddlewareAdapter;
use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Client;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\ItemPipeline\Processors\FakeProcessor;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\Timing\FakeClock;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\Processor;
use RoachPHP\Support\Configurable;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;
use RoachPHP\Tests\IntegrationTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @group integration
 */
final class EngineTest extends IntegrationTestCase
{
    use InteractsWithRequestsAndResponses;

    private Engine $engine;

    private FakeClock $clock;

    private ArrayRequestScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = new EventDispatcher();
        $this->clock = new FakeClock();
        $this->scheduler = new ArrayRequestScheduler($this->clock);
        $this->engine = new Engine(
            $this->scheduler,
            new Downloader(new Client(), $dispatcher),
            new ItemPipeline($dispatcher),
            new Processor($dispatcher),
            $dispatcher,
        );

        $_SERVER['__parse.called'] = 0;
    }

    public function testCrawlsStartUrls(): void
    {
        $startRequests = [
            $this->makeRequest('http://localhost:8000/test1'),
            $this->makeRequest('http://localhost:8000/test2'),
        ];
        $run = new Run($startRequests, '::namespace::');

        $this->engine->start($run);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testDoesntCrawlStartUrlsWithExistingRequestsInScheduler(): void
    {
        $startRequests = [
            $this->makeRequest('http://localhost:8000/test1'),
            $this->makeRequest('http://localhost:8000/test2'),
        ];

        $run = new Run($startRequests, '::namespace::');

        $this->scheduler->schedule($this->makeRequest('http://localhost:8000/test3'));

        $this->engine->start($run);

        $this->assertRouteWasNotCrawled('/test1');
        $this->assertRouteWasNotCrawled('/test2');
        $this->assertRouteWasCrawledTimes('/test3', 1);
    }

    public function testCrawlUrlsReturnedFromParseCallback(): void
    {
        $parseFunction = static function (Response $response) {
            foreach ($response->filter('a')->links() as $link) {
                yield ParseResult::request('GET', $link->getUri(), static fn (Response $response) => yield from []);
            }
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test2', $parseFunction)],
            '::namespace::',
        );

        $this->engine->start($run);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test3', 1);
    }

    public function testCallCorrectParseCallbackForRequest(): void
    {
        $parseCallback = static function () {
            yield ParseResult::request('GET', 'http://localhost:8000/test2', static function () {
                ++$_SERVER['__parse.called'];

                yield from [];
            });
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test1', $parseCallback)],
            '::namespace::',
        );

        $this->engine->start($run);

        self::assertEquals(1, $_SERVER['__parse.called']);
    }

    public function testSendItemsThroughItemPipeline(): void
    {
        $processor = new FakeProcessor();
        $startRequests = [
            $this->makeRequest('http://localhost:8000/test1', static function (Response $response) {
                yield ParseResult::item([
                    'title' => $response->filter('h1#headline')->text(),
                ]);
            }),
        ];
        $run = new Run(
            $startRequests,
            '::namespace::',
            itemProcessors: [$processor],
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => 'Such headline, wow']));
    }

    public function testHandleBothRequestAndItemEmittedFromSameParseCallback(): void
    {
        $processor = new FakeProcessor();
        $parseCallback = function () {
            yield ParseResult::item(['title' => '::title::']);

            yield ParseResult::fromValue($this->makeRequest('http://localhost:8000/test2'));
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test1', $parseCallback)],
            '::namespace::',
            itemProcessors: [$processor],
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => '::title::']));
        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }

    public function testRegisterExtensions(): void
    {
        $logger = new FakeLogger();
        $parseCallback = static function () {
            yield ParseResult::item(['title' => '::title::']);
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test1', $parseCallback)],
            '::namespace::',
            extensions: [
                new StatsCollectorExtension($logger, new FakeClock()),
                new LoggerExtension($logger),
            ],
        );

        $this->engine->start($run);

        self::assertTrue($logger->messageWasLogged('info', 'Run starting'));
        self::assertTrue($logger->messageWasLogged('info', 'Dispatching request', [
            'uri' => 'http://localhost:8000/test1',
        ]));
        self::assertTrue($logger->messageWasLogged('info', 'Item scraped', [
            'title' => '::title::',
        ]));
        self::assertTrue($logger->messageWasLogged('info', 'Run finished'));
        self::assertTrue($logger->messageWasLogged('info', 'Run statistics', [
            'duration' => '00:00:00',
            'requests.sent' => 1,
            'requests.dropped' => 0,
            'items.scraped' => 1,
            'items.dropped' => 0,
        ]));
    }

    public function testCollectAndReturnScrapedItems(): void
    {
        $parseCallback = static function () {
            yield ParseResult::item(['::key-1::' => '::value-1::']);

            yield ParseResult::item(['::key-2::' => '::value-2::']);
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test1', $parseCallback)],
            '::namespace::',
        );

        $result = $this->engine->collect($run);

        self::assertEquals([
            new Item(['::key-1::' => '::value-1::']),
            new Item(['::key-2::' => '::value-2::']),
        ], $result);
    }

    public function testReplaceDroppedRequestsWithoutWaitingForConfiguredDelay(): void
    {
        $middleware = new class() implements RequestMiddlewareInterface {
            use Configurable;

            public function handleRequest(Request $request): Request
            {
                if (\in_array($request->getUri(), ['http://localhost:8000/test1', 'http://localhost:8000/test2'], true)) {
                    return $request->drop('dropping');
                }

                return $request;
            }
        };
        $run = new Run(
            [
                $this->makeRequest('http://localhost:8000/test1'),
                $this->makeRequest('http://localhost:8000/test2'),
                $this->makeRequest('http://localhost:8000/test3'),
                $this->makeRequest('http://localhost:8000/robots'),
            ],
            '::namespace::',
            downloaderMiddleware: [DownloaderMiddlewareAdapter::fromMiddleware($middleware)],
            concurrency: 1,
            requestDelay: 5,
        );

        $this->engine->start($run);

        $this->assertRouteWasNotCrawled('/test1');
        $this->assertRouteWasNotCrawled('/test2');
        $this->assertRouteWasCrawledTimes('/test3', 1);
        $this->assertRouteWasCrawledTimes('/robots', 1);

        // 1) Request to `/test1` gets dropped by middleware
        // 2) Immediately request next request since configured `concurrency`
        //    has not been reached yet -> 0s passed
        // 3) Request to `/test2` gets dropped by middleware
        // 4) Immediately request next request since configured `concurrency`
        //    has not been reached yet -> 0s passed
        // 5) Request to `/test3` gets scheduled successfully.
        // 6) Send requests as maximum number of concurrent requests (1) have
        //    been scheduled successfully.
        // 7) Request next requests. Should wait configured delay between
        //    batches -> 5s passed
        // 8) Send requests as maximum number of concurrent requests (1) have
        //    been scheduled successfully.
        // 9) No more requests exist in scheduler. Run ends.
        //
        // Total wait time => 5s
        self::assertEquals(5, $this->clock->timePassed());
    }
}
