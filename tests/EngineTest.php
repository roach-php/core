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
use Generator;
use Sassnowski\Roach\Core\Engine;
use Sassnowski\Roach\Core\Run;
use Sassnowski\Roach\Downloader\Downloader;
use Sassnowski\Roach\Http\Client;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\ItemPipeline\Processors\FakeProcessor;
use Sassnowski\Roach\Parsing\MiddlewareStack as ResponseMiddleware;
use Sassnowski\Roach\Parsing\ParseResult;
use Sassnowski\Roach\Scheduling\ArrayRequestScheduler;
use Sassnowski\Roach\Scheduling\Timing\FakeClock;
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

    private ImmutableItemPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new FakeLogger();
        $this->pipeline = new ImmutableItemPipeline($this->logger);
        $this->engine = new Engine(
            new ArrayRequestScheduler(new FakeClock()),
            new Downloader(new Client()),
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
            [],
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
            [],
            $this->pipeline,
            ResponseMiddleware::create(),
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
            [$this->createRequest('http://localhost:8000/test1', $parseCallback)],
            [],
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
            [],
            $this->pipeline->setProcessors($processor),
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => 'Such headline, wow']));
    }

    public function testHandleBothRequestAndItemEmittedFromSameParseCallback(): void
    {
        $processor = new FakeProcessor();
        $parseCallback = function () {
            yield ParseResult::item(['title' => '::title::']);

            yield ParseResult::fromValue($this->createRequest('http://localhost:8000/test2'));
        };
        $run = new Run(
            [$this->createRequest('http://localhost:8000/test1', $parseCallback)],
            [],
            $this->pipeline->setProcessors($processor),
            ResponseMiddleware::create(),
        );

        $this->engine->start($run);

        $processor->assertCalledWith(new Item(['title' => '::title::']));
        $this->assertRouteWasCrawledTimes('/test1', 1);
        $this->assertRouteWasCrawledTimes('/test2', 1);
    }
}
