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
use Sassnowski\Roach\Http\Middleware\MiddlewareStack as HttpMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Parsing\MiddlewareStack as ResponseMiddleware;
use Sassnowski\Roach\Parsing\ParseResult;
use Sassnowski\Roach\Queue\RequestQueue;
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
        HttpMiddleware $middlewareStack,
        ItemPipelineInterface $itemPipeline,
        ResponseMiddleware $responseMiddleware,
    ): void {
        foreach ($startRequests as $request) {
            $this->scheduleRequest($request);
        }

        $this->work($middlewareStack, $itemPipeline, $responseMiddleware);
    }

    private function work(
        HttpMiddleware $middlewareStack,
        ItemPipelineInterface $pipeline,
        ResponseMiddleware $responseMiddleware,
    ): void {
        while (!$this->requestQueue->empty()) {
            $requests = function () use ($middlewareStack, $pipeline, $responseMiddleware) {
                foreach ($this->requestQueue->all() as $request) {
                    yield fn () => $this->sendRequest($request, $middlewareStack, $pipeline, $responseMiddleware);
                }
            };

            $this->client->pool($requests())->wait();
        }
    }

    private function onFulfilled(
        Response $response,
        ItemPipelineInterface $itemPipeline,
        ResponseMiddleware $responseMiddleware,
    ): void {
        /** @var ParseResult[] $parseResults */
        $parseResults = $responseMiddleware->handle($response);

        foreach ($parseResults as $result) {
            $result->apply(
                fn (Request $request) => $this->scheduleRequest($request),
                static fn (ItemInterface $item) => $itemPipeline->sendItem($item),
            );
        }
    }

    private function sendRequest(
        Request $request,
        HttpMiddleware $middlewareStack,
        ItemPipelineInterface $itemPipeline,
        ResponseMiddleware $responseMiddleware,
    ): ?PromiseInterface {
        $promise = $middlewareStack->dispatchRequest(
            $request,
            fn (Request $req) => $this->client->dispatch($req)->then(
                static fn (ResponseInterface $response) => new Http\Response($response, $req),
            ),
        );

        return $promise
            ?->then(function (Response $response) use ($itemPipeline, $responseMiddleware): void {
                $this->onFulfilled($response, $itemPipeline, $responseMiddleware);
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
