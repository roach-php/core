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

use Exception;
use RoachPHP\Events\ExceptionReceived;
use RoachPHP\Events\ExceptionReceiving;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\ResponseDropped;
use RoachPHP\Events\ResponseReceived;
use RoachPHP\Events\ResponseReceiving;
use RoachPHP\Http\ClientInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\RequestException;
use RoachPHP\Http\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Downloader
{
    /**
     * @var array<array-key, DownloaderMiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @var list<Request>
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

    public function prepare(Request $request, ?callable $onRejected): void
    {
        try {
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
        } catch (Exception $exception) {
            $this->onExceptionReceived($exception, $request, $onRejected);
        }
    }

    public function flush(?callable $onFullFilled = null, ?callable $onRejected = null): void
    {
        $requests = $this->requests;

        $this->requests = [];

        foreach ($requests as $key => $request) {
            if ($request->getResponse() !== null) {
                $this->onResponseReceived($request->getResponse(), $onFullFilled);

                unset($requests[$key]);
            }
        }

        if (\count($requests) === 0) {
            return;
        }

        $this->client->pool(
            \array_values($requests),
            function (Response $response) use ($onFullFilled): void {
                $this->onResponseReceived($response, $onFullFilled);
            },
            function (RequestException $requestException) use ($onRejected): void {
                $this->onExceptionReceived(
                    $requestException->getReason(),
                    $requestException->getRequest(),
                    $onRejected,
                );
            }
        );
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
                $this->eventDispatcher->dispatch(
                    new ResponseDropped($response),
                    ResponseDropped::NAME,
                );

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

    private function onExceptionReceived(\Throwable $exception, Request $request, ?callable $callback): void
    {
        $this->eventDispatcher->dispatch(
            new ExceptionReceiving($exception),
            ExceptionReceiving::NAME,
        );

        foreach ($this->middleware as $middleware) {
            $request = $middleware->handleException($exception, $request);
        }

        $this->eventDispatcher->dispatch(
            new ExceptionReceived($exception),
            ExceptionReceived::NAME,
        );

        if (null !== $callback) {
            $callback($exception, $request);
        }
    }
}
