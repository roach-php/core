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

namespace Sassnowski\Roach;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use Iterator;
use Psr\Http\Message\ResponseInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\Pipeline;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Queue\RequestQueue;
use Sassnowski\Roach\Spider\ParseResult;

final class Engine
{
    public function __construct(
        private array $startRequests,
        private RequestQueue $requestQueue,
        private MiddlewareStack $middlewareStack,
        private Pipeline $itemPipeline,
        private Client $client,
    ) {
    }

    public static function create(
        array $startRequests,
        ?RequestQueue $requestQueue = null,
        array $middleware = [],
        array $itemProcessors = [],
        ?Client $client = null,
    ): self {
        return new self(
            $startRequests,
            $requestQueue ?: new ArrayRequestQueue(),
            MiddlewareStack::create(...$middleware),
            new Pipeline($itemProcessors),
            $client ?: new Client(),
        );
    }

    public function start(): void
    {
        foreach ($this->startRequests as $request) {
            $this->scheduleRequest($request);
        }

        $this->work();
    }

    private function work(): void
    {
        while (!$this->requestQueue->empty()) {
            $requests = function () {
                foreach ($this->requestQueue->dequeue() as $request) {
                    yield function () use ($request) {
                        return $this->sendRequest($request);
                    };
                }
            };

            $this->sendRequestsConcurrently($requests());
        }
    }

    private function onFulfilled(Http\Response $response, Request $request): void
    {
        /** @var ParseResult[] $parseResults */
        $parseResults = ($request->callback)($response);

        foreach ($parseResults as $result) {
            $result->isRequest()
                ? $this->scheduleRequest($result->getRequest())
                : $this->itemPipeline->sendThroughPipeline($result->getItem());
        }
    }

    private function sendRequest(Request $request): ?PromiseInterface
    {
        return $this->middlewareStack->dispatchRequest(
            $request,
            fn (Request $req) => $this->client->sendAsync($req)->then(
                static fn (ResponseInterface $response) => new Http\Response($response, $req),
            ),
        )?->then(function (Response $response) use ($request): void {
            $this->onFulfilled($response, $request);
        });
    }

    private function scheduleRequest(Request $request): void
    {
        $this->requestQueue->enqueue($request);
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
