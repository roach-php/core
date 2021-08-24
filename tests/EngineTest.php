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

namespace RoachPHP\Tests;

use RoachPHP\Core\Engine;
use RoachPHP\Core\Run;
use RoachPHP\Downloader\Downloader;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Http\Client;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\ItemPipeline\Processors\FakeProcessor;
use RoachPHP\ResponseProcessing\ParseResult;
use RoachPHP\ResponseProcessing\Processor;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\Timing\FakeClock;

/**
 * @internal
 * @group integration
 */
final class EngineTest extends IntegrationTest
{
    use InteractsWithRequestsAndResponses;

    private Engine $engine;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new FakeDispatcher();
        $this->engine = new Engine(
            new ArrayRequestScheduler(new FakeClock()),
            new Downloader(new Client(), $this->dispatcher),
            new ItemPipeline($this->dispatcher),
            new Processor($this->dispatcher),
            $this->dispatcher,
        );

        $_SERVER['__parse.called'] = 0;
    }

    public function testCrawlsStartUrls(): void
    {
        $startRequests = [
            $this->makeRequest('http://localhost:8000/test1'),
            $this->makeRequest('http://localhost:8000/test2'),
        ];
        $run = new Run($startRequests, [], [], []);

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
            [$this->makeRequest('http://localhost:8000/test2', $parseFunction)],
            [],
            [],
            [],
        );

        $this->engine->start($run);

        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test3', 1);
    }

    public function testCallCorrectParseCallbackForRequest(): void
    {
        $parseCallback = static function () {
            yield ParseResult::request('http://localhost:8000/test2', static function () {
                ++$_SERVER['__parse.called'];

                yield from [];
            });
        };
        $run = new Run(
            [$this->makeRequest('http://localhost:8000/test1', $parseCallback)],
            [],
            [],
            [],
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
            [],
            [$processor],
            [],
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
            [],
            [$processor],
            [],
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => '::title::']));
        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }
}
