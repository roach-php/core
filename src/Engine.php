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

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http\ClientInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Queue\RequestQueue;
use Sassnowski\Roach\Spider\ParseResult;
use Throwable;

final class Engine
{
    public function __construct(
        private RequestQueue $requestQueue,
        private ClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    public function start(
        array $startRequests,
        MiddlewareStack $middlewareStack,
        ItemPipelineInterface $itemPipeline,
    ): void {
        foreach ($startRequests as $request) {
            $this->scheduleRequest($request);
        }

        $this->work($middlewareStack, $itemPipeline);
    }

    private function work(MiddlewareStack $middlewareStack, ImmutableItemPipeline $pipeline): void
    {
        while (!$this->requestQueue->empty()) {
            $requests = function () use ($middlewareStack, $pipeline) {
                foreach ($this->requestQueue->all() as $request) {
                    yield fn () => $this->sendRequest($request, $middlewareStack, $pipeline);
                }
            };

            $this->client->pool($requests())->wait();
        }
    }

    private function onFulfilled(Response $response, ImmutableItemPipeline $itemPipeline): void
    {
        /** @var ParseResult[] $parseResults */
        $parseResults = $response->getRequest()->callback($response);

        foreach ($parseResults as $result) {
            $result->apply(
                fn (Request $request) => $this->scheduleRequest($request),
                static fn (ItemInterface $item) => $itemPipeline->sendItem($item),
            );
        }
    }

    private function sendRequest(
        Request $request,
        MiddlewareStack $middlewareStack,
        ImmutableItemPipeline $itemPipeline,
    ): ?PromiseInterface {
        $promise = $middlewareStack->dispatchRequest(
            $request,
            fn (Request $req) => $this->client->dispatch($req)->then(
                static fn (ResponseInterface $response) => new Http\Response($response, $req),
            ),
        );

        return $promise
            ?->then(function (Response $response) use ($itemPipeline): void {
                $this->onFulfilled($response, $itemPipeline);
            }, function (Throwable $e) use ($request): void {
                $this->logger->error('[Engine] Error while dispatching request', [
                    'uri' => $request->getUri(),
                    'exception' => $e,
                ]);
            })
            ->otherwise(function (Throwable $e) use ($request): void {
                $this->logger->error('[Engine] Error while processing response', [
                    'uri' => $request->getUri(),
                    'exception' => $e,
                ]);
            });
    }

    private function scheduleRequest(Request $request): void
    {
        $this->requestQueue->queue($request);
    }
}
