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

namespace Sassnowski\Roach;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Iterator;
use Psr\Http\Message\ResponseInterface;
use Sassnowski\Roach\Events\ItemEmitted;
use Sassnowski\Roach\Events\RequestDropped;
use Sassnowski\Roach\Events\RequestScheduling;
use Sassnowski\Roach\Events\RequestSending;
use Sassnowski\Roach\Events\RequestSent;
use Sassnowski\Roach\Events\ResponseReceived;
use Sassnowski\Roach\Events\RunFinished;
use Sassnowski\Roach\Scheduler\Scheduler;
use Sassnowski\Roach\Spider\AbstractSpider;
use Sassnowski\Roach\Spider\ParseResult;
use Sassnowski\Roach\Spider\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Engine
{
    private Client $client;

    public function __construct(
        private AbstractSpider $spider,
        private Scheduler $scheduler,
        private EventDispatcherInterface $dispatcher,
        ?Client $client = null,
    ) {
        $this->client = $client ?: new Client();
    }

    public function start(): void
    {
        foreach ($this->spider->startRequests() as $request) {
            $this->scheduleRequest($request);
        }

        $this->work();

        $this->dispatcher->dispatch(
            new RunFinished(),
            RunFinished::NAME,
        );
    }

    private function work(): void
    {
        while ($this->scheduler->hasRequests()) {
            $requests = function () {
                foreach ($this->scheduler->nextRequests() as $request) {
                    yield function () use ($request) {
                        return $this->sendRequest($request);
                    };
                }
            };

            $this->sendRequestsConcurrently($requests());
        }
    }

    private function onFulfilled(Response $response, Request $request): void
    {
        $this->dispatcher->dispatch(
            new ResponseReceived($response, $request),
            ResponseReceived::NAME,
        );

        $crawler = new Crawler((string) $response->getBody(), $this->spider->baseUri);

        /** @var ParseResult[] $parseResults */
        $parseResults = $this->spider->{$request->parseMethod}($crawler);

        foreach ($parseResults as $result) {
            $request = $this->handleParseResult($result);

            if (null !== $request) {
                $this->scheduleRequest($request);
            }
        }
    }

    private function handleParseResult(ParseResult $result): ?Request
    {
        if ($result->isItem()) {
            $this->dispatcher->dispatch(
                new ItemEmitted($result->getItem()),
                ItemEmitted::NAME,
            );

            return null;
        }

        return $result->getRequest();
    }

    private function sendRequest(Request $request): PromiseInterface
    {
        $this->dispatcher->dispatch(
            new RequestSending($request),
            RequestSending::NAME,
        );

        $promise = $this->client->sendAsync($request)
            ->then(function (ResponseInterface $response) use ($request): void {
                $this->onFulfilled($response, $request);
            });

        $this->dispatcher->dispatch(
            new RequestSent($request),
            RequestSent::NAME,
        );

        return $promise;
    }

    private function scheduleRequest(Request $request): void
    {
        $this->dispatcher->dispatch(
            new RequestScheduling($request),
            RequestScheduling::NAME,
        );

        if ($request->wasDropped()) {
            $this->dispatcher->dispatch(
                new RequestDropped($request),
                RequestDropped::NAME,
            );

            return;
        }

        $this->scheduler->scheduleRequest($request);
    }

    private function sendRequestsConcurrently(array | Iterator $requests): void
    {
        $pool = new Pool($this->client, $requests, [
            'concurrency' => 2,
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }
}
