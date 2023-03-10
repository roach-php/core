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

namespace RoachPHP\Tests\Downloader\Middleware;

use RoachPHP\Core\Engine;
use RoachPHP\Core\Run;
use RoachPHP\Downloader\Downloader;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Downloader\Middleware\DownloaderMiddlewareAdapter;
use RoachPHP\Downloader\Middleware\RobotsTxtMiddleware;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Http\Client;
use RoachPHP\Http\Request;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\Timing\FakeClock;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\Processor;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\IntegrationTestCase;

/**
 * @internal
 */
final class RobotsTxtMiddlewareTestCase extends IntegrationTestCase
{
    use InteractsWithRequestsAndResponses;

    private Engine $engine;

    private DownloaderMiddlewareInterface $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = new FakeDispatcher();
        $this->engine = new Engine(
            new ArrayRequestScheduler(new FakeClock()),
            new Downloader(new Client(), $dispatcher),
            new ItemPipeline($dispatcher),
            new Processor($dispatcher),
            $dispatcher,
        );

        $middleware = new RobotsTxtMiddleware();
        $middleware->configure(['fileName' => 'robots']);
        $this->middleware = DownloaderMiddlewareAdapter::fromMiddleware($middleware);
    }

    public function testOnlyRequestsRobotsTxtOnceForRequestsToSameDomain(): void
    {
        $parseCallback = fn () => yield ParseResult::fromValue(self::makeRequest('http://localhost:8000/test2'));
        $run = new Run(
            [new Request('GET', 'http://localhost:8000/test1', $parseCallback)],
            downloaderMiddleware: [$this->middleware],
        );

        $this->engine->start($run);

        $this->assertRouteWasCrawledTimes('/robots', 1);
    }

    public function testAllowsRequestIfAllowedByRobotsTxt(): void
    {
        $run = new Run(
            [self::makeRequest('http://localhost:8000/test1')],
            downloaderMiddleware: [$this->middleware],
        );

        $this->engine->start($run);

        $this->assertRouteWasCrawled('/test1');
    }

    public function testDropRequestIfForbiddenByRobotsTxt(): void
    {
        $run = new Run(
            [self::makeRequest('http://localhost:8000/test2')],
            downloaderMiddleware: [$this->middleware],
        );

        $this->engine->start($run);

        $this->assertRouteWasNotCrawled('/test2');
    }
}
