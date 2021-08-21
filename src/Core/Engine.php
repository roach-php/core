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

namespace Sassnowski\Roach\Core;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http;
use Sassnowski\Roach\Http\ClientInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\Parsing\ParseResult;
use Sassnowski\Roach\Scheduling\RequestSchedulerInterface;
use Throwable;

final class Engine
{
    public function __construct(
        private RequestSchedulerInterface $scheduler,
        private ClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    public function start(Run $run): void
    {
        $this->scheduler->setBatchSize($run->concurrency());
        $this->scheduler->setDelay($run->requestDelay());

        foreach ($run->startRequests() as $request) {
            $this->scheduleRequest($request);
        }

        $this->work($run);
    }

    private function work(Run $run): void
    {
        while (!$this->scheduler->empty()) {
            $requests = function () use ($run) {
                foreach ($this->scheduler->nextRequests() as $request) {
                    yield fn () => $this->sendRequest($request, $run);
                }
            };

            $this->client->pool($requests())->wait();
        }
    }

    private function onFulfilled(Response $response, Run $run): void
    {
        /** @var ParseResult[] $parseResults */
        $parseResults = $run->responseMiddleware()->handle($response);

        foreach ($parseResults as $result) {
            $result->apply(
                fn (Request $request) => $this->scheduleRequest($request),
                static fn (ItemInterface $item) => $run->itemPipeline()->sendItem($item),
            );
        }
    }

    private function sendRequest(Request $request, Run $run): ?PromiseInterface
    {
        $promise = $run->httpMiddleware()->dispatchRequest(
            $request,
            fn (Request $req) => $this->client->dispatch($req)->then(
                static fn (ResponseInterface $response) => new Http\Response($response, $req),
            ),
        );

        return $promise
            ?->then(function (Response $response) use ($run): void {
                $this->onFulfilled($response, $run);
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
        $this->scheduler->schedule($request);
    }
}
