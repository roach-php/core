<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader;

use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\ResponseDropped;
use RoachPHP\Events\ResponseReceived;
use RoachPHP\Events\ResponseReceiving;
use RoachPHP\Http\ClientInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Downloader
{
    /**
     * @var array<DownloaderMiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @var array<Request>
     */
    private array $requests = [];

    public function __construct(
        private ClientInterface $client,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function withMiddleware(DownloaderMiddlewareInterface ...$middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function scheduledRequests(): int
    {
        return \count($this->requests);
    }

    public function prepare(Request $request): void
    {
        foreach ($this->middleware as $middleware) {
            $request = $middleware->handleRequest($request);

            if ($request->wasDropped()) {
                $this->eventDispatcher->dispatch(
                    new RequestDropped($request),
                    RequestDropped::NAME,
                );

                return;
            }
        }

        /**
         * @psalm-suppress UnnecessaryVarAnnotation
         *
         * @var RequestSending $event
         */
        $event = $this->eventDispatcher->dispatch(
            new RequestSending($request),
            RequestSending::NAME,
        );

        if ($event->request->wasDropped()) {
            $this->eventDispatcher->dispatch(
                new RequestDropped($event->request),
                RequestDropped::NAME,
            );

            return;
        }

        $this->requests[] = $event->request;
    }

    public function flush(?callable $callback = null): void
    {
        $requests = $this->requests;

        $this->requests = [];

        foreach ($requests as $key => $request) {
            if ($request->getResponse() !== null) {
                $this->onResponseReceived($request->getResponse(), $callback);

                unset($requests[$key]);
            }
        }

        if (\count($requests) === 0) {
            return;
        }

        $this->client->pool(\array_values($requests), function (Response $response) use ($callback): void {
            $this->onResponseReceived($response, $callback);
        });
    }

    private function onResponseReceived(Response $response, ?callable $callback): void
    {
        $event = new ResponseReceiving($response);
        $this->eventDispatcher->dispatch($event, ResponseReceiving::NAME);
        $response = $event->response;

        if ($response->wasDropped()) {
            $this->eventDispatcher->dispatch(
                new ResponseDropped($response),
                ResponseDropped::NAME,
            );

            return;
        }

        foreach ($this->middleware as $middleware) {
            $response = $middleware->handleResponse($response);

            if ($response->wasDropped()) {
                return;
            }
        }

        $event = new ResponseReceived($response);
        $this->eventDispatcher->dispatch($event, ResponseReceived::NAME);
        $response = $event->response;

        if ($response->wasDropped()) {
            $this->eventDispatcher->dispatch(
                new ResponseDropped($response),
                ResponseDropped::NAME,
            );

            return;
        }

        if (null !== $callback) {
            $callback($response);
        }
    }
}
