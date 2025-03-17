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

namespace RoachPHP\Tests\Downloader;

use Exception;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Downloader;
use RoachPHP\Downloader\Middleware\FakeMiddleware;
use RoachPHP\Events\ExceptionReceived;
use RoachPHP\Events\ExceptionReceiving;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\ResponseDropped;
use RoachPHP\Events\ResponseReceived;
use RoachPHP\Events\ResponseReceiving;
use RoachPHP\Http\FakeClient;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class DownloaderTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private Downloader $downloader;

    private FakeClient $client;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->client = new FakeClient();
        $this->dispatcher = new FakeDispatcher();
        $this->downloader = new Downloader($this->client, $this->dispatcher);
    }

    public function testSendRequests(): void
    {
        $requestA = $this->makeRequest('::url-a::');
        $requestB = $this->makeRequest('::url-a::');

        $this->downloader->prepare($requestA, null);
        $this->downloader->prepare($requestB, null);
        $this->downloader->flush();

        $this->client->assertRequestWasSent($requestA);
        $this->client->assertRequestWasSent($requestB);
    }

    public function testPassRequestsThroughRequestHandlersInOrder(): void
    {
        $initialRequest = $this->makeRequest();
        $middlewareARequest = $this->makeRequest();
        $middlewareA = new FakeMiddleware(static fn () => $middlewareARequest);
        $middlewareB = new FakeMiddleware();

        $this->downloader
            ->withMiddleware($middlewareA, $middlewareB)
            ->prepare($initialRequest, null);

        $middlewareA->assertRequestHandled($initialRequest);
        $middlewareB->assertRequestHandled($middlewareARequest);
    }

    public function testDoesNotPassOnRequestIfDroppedByMiddleware(): void
    {
        $initialRequest = $this->makeRequest();
        $dropMiddleware = new FakeMiddleware(static fn (Request $request) => $request->drop('::reason::'));
        $middleware = new FakeMiddleware();

        $this->downloader
            ->withMiddleware($dropMiddleware, $middleware)
            ->prepare($initialRequest, null);

        $dropMiddleware->assertRequestHandled($initialRequest);
        $middleware->assertNoRequestsHandled();
    }

    public function testDoesNotSendRequestIfDroppedByMiddleware(): void
    {
        $request = $this->makeRequest();
        $dropMiddleware = new FakeMiddleware(static fn (Request $request) => $request->drop('::reason::'));

        $this->downloader
            ->withMiddleware($dropMiddleware)
            ->prepare($request, null);
        $this->downloader->flush();

        $this->client->assertRequestWasNotSent($request);
    }

    public function testSendResponsesThroughMiddlewareInOrder(): void
    {
        $middlewareAResponse = $this->makeResponse();
        $middlewareBResponse = $this->makeResponse();
        $middlewareA = new FakeMiddleware(null, static fn () => $middlewareAResponse);
        $middlewareB = new FakeMiddleware(null, static fn () => $middlewareBResponse);
        $middlewareC = new FakeMiddleware();
        $this->downloader->withMiddleware($middlewareA, $middlewareB, $middlewareC);

        $this->downloader->prepare($this->makeRequest(), null);
        $this->downloader->flush();

        $middlewareB->assertResponseHandled($middlewareAResponse);
        $middlewareC->assertResponseHandled($middlewareBResponse);
    }

    public function testDontPassOnResponseIfDroppedByMiddleware(): void
    {
        $dropMiddleware = new FakeMiddleware(null, static fn (Response $response) => $response->drop('::reason::'));
        $middleware = new FakeMiddleware();
        $this->downloader->withMiddleware($dropMiddleware, $middleware);

        $this->downloader->prepare($this->makeRequest(), null);
        $this->downloader->flush();

        $middleware->assertNoResponseHandled();
    }

    public function testCallResponseCallbackForEachResponse(): void
    {
        $requests = [
            $this->makeRequest('::url-a::')->withMeta('index', 0),
            $this->makeRequest('::url-b::')->withMeta('index', 1),
        ];
        $this->downloader->prepare($requests[0], null);
        $this->downloader->prepare($requests[1], null);

        $this->downloader->flush(static function (Response $response) use (&$requests): void {
            self::assertContains($response->getRequest(), $requests);
            unset($requests[$response->getRequest()->getMeta('index')]);
        });
        self::assertEmpty($requests);
    }

    public function testDontCallResponseCallbackIfResponseWasDropped(): void
    {
        $called = false;
        $dropMiddleware = new FakeMiddleware(null, static fn (Response $response) => $response->drop('::reason::'));
        $this->downloader->withMiddleware($dropMiddleware);

        $this->downloader->prepare($this->makeRequest(), null);
        $this->downloader->flush(static function () use (&$called): void {
            $called = true;
        });

        self::assertFalse($called);
    }

    public function testDispatchesAnEventIfRequestWasDropped(): void
    {
        $request = $this->makeRequest();
        $dropMiddleware = new FakeMiddleware(static fn (Request $request) => $request->drop('::reason::'));
        $this->downloader->withMiddleware($dropMiddleware);

        $this->downloader->prepare($request, null);

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            static fn (RequestDropped $event) => $event->request->wasDropped() && $event->request->getUri() === $request->getUri(),
        );
    }

    public function testDoesNotDispatchEventIfRequestWasNotDropped(): void
    {
        $this->downloader->prepare($this->makeRequest(), null);

        $this->dispatcher->assertNotDispatched(RequestDropped::NAME);
    }

    public function testDispatchesAnEventBeforeRequestIsScheduled(): void
    {
        $request = $this->makeRequest();
        $this->downloader->prepare($request, null);

        $this->dispatcher->assertDispatched(
            RequestSending::NAME,
            static fn (RequestSending $event) => $event->request === $request,
        );
    }

    public function testDoesNotScheduleEventIfDroppedByEventListener(): void
    {
        $this->dispatcher->listen(RequestSending::NAME, static function (RequestSending $event): void {
            $event->request = $event->request->drop('::reason::');
        });
        $request = $this->makeRequest();

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $this->client->assertRequestWasNotSent($request);
    }

    public function testDispatchesAnEventIfRequestWasDroppedByListener(): void
    {
        $this->dispatcher->listen(RequestSending::NAME, static function (RequestSending $event): void {
            $event->request = $event->request->drop('::reason::');
        });
        $request = $this->makeRequest();

        $this->downloader->prepare($request, null);

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            static fn (RequestDropped $event) => $event->request->getUri() === $request->getUri(),
        );
    }

    public function testDispatchEventWhenResponseWasReceived(): void
    {
        $request = $this->makeRequest();

        $this->downloader->prepare($request, null);
        $this->dispatcher->assertNotDispatched(ResponseReceiving::NAME);

        $this->downloader->flush();
        $this->dispatcher->assertDispatched(
            ResponseReceiving::NAME,
            static fn (ResponseReceiving $event) => $event->response->getRequest()->getUri() === $request->getUri(),
        );
    }

    public function testDoesNotDispatchEventIfResponseWasNotDropped(): void
    {
        $this->downloader->prepare($this->makeRequest());
        $this->downloader->flush();

        $this->dispatcher->assertNotDispatched(ResponseDropped::NAME);
    }

    public function testDispatchesAnEventIfResponseWasDropped(): void
    {
        $request = $this->makeRequest();
        $dropMiddleware = new FakeMiddleware(null, static fn (Response $response) => $response->drop('::reason::'));
        $this->downloader->withMiddleware($dropMiddleware);

        $this->downloader->prepare($request);
        $this->downloader->flush();

        $this->dispatcher->assertDispatched(
            ResponseDropped::NAME,
            static fn (ResponseDropped $event) => $event->response->wasDropped() && $event->response->getUri() === $request->getUri(),
        );
    }

    public function testDontPassResponseToMiddlewareIfDroppedByExtension(): void
    {
        $request = $this->makeRequest();
        $this->dispatcher->listen(ResponseReceiving::NAME, static function (ResponseReceiving $event): void {
            $event->response = $event->response->drop('::reason::');
        });
        $middleware = new FakeMiddleware();
        $this->downloader->withMiddleware($middleware);

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $middleware->assertNoResponseHandled();
    }

    public function testFireEventIfReceivedResponseWasDroppedByExtension(): void
    {
        $request = $this->makeRequest();
        $this->dispatcher->listen(ResponseReceiving::NAME, static function (ResponseReceiving $event): void {
            $event->response = $event->response->drop('::reason::');
        });

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $this->dispatcher->assertDispatched(
            ResponseDropped::NAME,
            static fn (ResponseDropped $event) => $event->response->getRequest()->getUri() === $request->getUri(),
        );
    }

    public function testDontCallParseCallbackIfRequestWasDroppedByExtension(): void
    {
        $called = false;
        $request = $this->makeRequest();
        $this->dispatcher->listen(ResponseReceiving::NAME, static function (ResponseReceiving $event): void {
            $event->response = $event->response->drop('::reason::');
        });

        $this->downloader->prepare($request, null);
        $this->downloader->flush(static function () use (&$called): void {
            $called = true;
        });

        self::assertFalse($called);
    }

    public function testDispatchEventWhenResponseWasProcessedByMiddleware(): void
    {
        $request = $this->makeRequest();
        $middleware = new FakeMiddleware(
            responseHandler: function (Response $response) {
                $this->dispatcher->assertNotDispatched(ResponseReceived::NAME);

                return $response;
            },
        );
        $this->downloader->withMiddleware($middleware);

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $this->dispatcher->assertDispatched(
            ResponseReceived::NAME,
            static fn (ResponseReceived $event) => $event->response->getRequest()->getUri() === $request->getUri(),
        );
    }

    public function testFireEventIfProcessedResponseWasDroppedByExtension(): void
    {
        $request = $this->makeRequest();
        $this->dispatcher->listen(ResponseReceived::NAME, static function (ResponseReceived $event): void {
            $event->response = $event->response->drop('::reason::');
        });

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $this->dispatcher->assertDispatched(
            ResponseDropped::NAME,
            static fn (ResponseDropped $event) => $event->response->getRequest()->getUri() === $request->getUri(),
        );
    }

    public function testDontCallParseCallbackIfProcessedResponseWasDroppedByExtension(): void
    {
        $called = false;
        $request = $this->makeRequest();
        $this->dispatcher->listen(ResponseReceived::NAME, static function (ResponseReceived $event): void {
            $event->response = $event->response->drop('::reason::');
        });

        $this->downloader->prepare($request, null);
        $this->downloader->flush(static function () use (&$called): void {
            $called = true;
        });

        self::assertFalse($called);
    }

    public function testDontSendRequestIfHasResponse(): void
    {
        $request = $this->makeRequest();
        $request = $request->withResponse($this->makeResponse($request));

        $this->downloader->prepare($request, null);
        $this->downloader->flush();

        $this->client->assertRequestWasNotSent($request);
    }

    public function testResponseDispatchedWhenNotSent(): void
    {
        $request = $this->makeRequest();
        $request = $request->withResponse($this->makeResponse($request));

        $this->downloader->prepare($request, null);
        $this->dispatcher->assertNotDispatched(ResponseReceiving::NAME);

        $this->downloader->flush();
        $this->dispatcher->assertDispatched(
            ResponseReceiving::NAME,
            static fn (ResponseReceiving $event) => $event->response->getRequest()->getUri() === $request->getUri(),
        );

        $this->client->assertRequestWasNotSent($request);
    }

    public function testPassRequestsThroughRequestHandlersWhenHasResponse(): void
    {
        $request = $this->makeRequest();
        $request = $request->withResponse($this->makeResponse($request));

        $middleware = new FakeMiddleware();

        $this->downloader
            ->withMiddleware($middleware)
            ->prepare($request, null);

        $middleware->assertRequestHandled($request);
    }

    public function testCallsOnRejectedCallbackWhenExceptionOccursDuringRequestHandling(): void
    {
        $called = false;
        $request = $this->makeRequest();
        $exceptionMiddleware = new FakeMiddleware(static fn (Request $request) => throw new \Exception('Oh no!'));

        $this->downloader
            ->withMiddleware($exceptionMiddleware)
            ->prepare($request, function () use (&$called) {
                $called = true;
            });
        $this->downloader->flush();

        $this->client->assertRequestWasNotSent($request);
        self::assertTrue($called);
    }

    public function testCallsOnRejectedCallbackWhenExceptionOccursDuringFlushing(): void
    {
        $called = false;
        $request = $this->makeRequest();
        $this->client->makeRequestsFail($request);

        $this->downloader->prepare($request, null);
        $this->downloader->flush(onRejected: function () use (&$called) {
            $called = true;
        });

        self::assertTrue($called);
    }

    public function testPassExceptionsThroughExceptionHandlers(): void
    {
        $request = $this->makeRequest();
        $exception = new Exception('Oh no!');
        $exceptionMiddleware = new FakeMiddleware(static fn () => throw $exception);

        $this->downloader
            ->withMiddleware($exceptionMiddleware)
            ->prepare($request, null);

        $exceptionMiddleware->assertExceptionHandled($exception);
    }

    public function testDispatchEventIfExceptionIsBeingReceived(): void
    {
        $request = $this->makeRequest();
        $successfulMiddleware = new FakeMiddleware();
        $exception = new Exception('On no!');
        $failingMiddleware = new FakeMiddleware(static fn () => throw $exception);

        $this->downloader
            ->withMiddleware($successfulMiddleware)
            ->prepare($request, null);
        $this->dispatcher->assertNotDispatched(ExceptionReceiving::class);

        $this->downloader
            ->withMiddleware($failingMiddleware)
            ->prepare($request, null);

        $this->dispatcher->assertDispatched(
            ExceptionReceiving::NAME,
            static fn (ExceptionReceiving $event) => $event->exception == $exception,
        );
    }

    public function testDispatchEventWhenExceptionWasProcessedByMiddleware(): void
    {
        $request = $this->makeRequest();
        $exception = new Exception('On no!');
        $middleware = new FakeMiddleware(
            requestHandler: static fn () => throw $exception,
            exceptionHandler: function (Exception $exception, Request $request) {
                $this->dispatcher->assertNotDispatched(ExceptionReceived::NAME);

                return $request;
            },
        );
        $this->downloader->withMiddleware($middleware);

        $this->downloader->prepare($request, null);

        $this->dispatcher->assertDispatched(
            ExceptionReceived::NAME,
            static fn (ExceptionReceived $event) => $event->exception === $exception,
        );
    }
}
