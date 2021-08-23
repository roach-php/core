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

namespace Sassnowski\Roach\Tests\Downloader;

use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Downloader\Downloader;
use Sassnowski\Roach\Downloader\Middleware\FakeMiddleware;
use Sassnowski\Roach\Events\FakeDispatcher;
use Sassnowski\Roach\Events\RequestDropped;
use Sassnowski\Roach\Events\RequestSending;
use Sassnowski\Roach\Http\FakeClient;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Tests\InteractsWithRequestsAndResponses;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

        $this->downloader->prepare($requestA);
        $this->downloader->prepare($requestB);
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
            ->prepare($initialRequest);

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
            ->prepare($initialRequest);

        $dropMiddleware->assertRequestHandled($initialRequest);
        $middleware->assertNoRequestsHandled();
    }

    public function testDoesNotSendRequestIfDroppedByMiddleware(): void
    {
        $request = $this->makeRequest();
        $dropMiddleware = new FakeMiddleware(static fn (Request $request) => $request->drop('::reason::'));

        $this->downloader
            ->withMiddleware($dropMiddleware)
            ->prepare($request);
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

        $this->downloader->prepare($this->makeRequest());
        $this->downloader->flush();

        $middlewareB->assertResponseHandled($middlewareAResponse);
        $middlewareC->assertResponseHandled($middlewareBResponse);
    }

    public function testDontPassOnResponseIfDroppedByMiddleware(): void
    {
        $dropMiddleware = new FakeMiddleware(null, static fn (Response $response) => $response->drop('::reason::'));
        $middleware = new FakeMiddleware();
        $this->downloader->withMiddleware($dropMiddleware, $middleware);

        $this->downloader->prepare($this->makeRequest());
        $this->downloader->flush();

        $middleware->assertNoResponseHandled();
    }

    public function testCallResponseCallbackForEachResponse(): void
    {
        $requests = [
            $this->makeRequest('::url-a::')->withMeta('index', 0),
            $this->makeRequest('::url-b::')->withMeta('index', 1),
        ];
        $this->downloader->prepare($requests[0]);
        $this->downloader->prepare($requests[1]);

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

        $this->downloader->prepare($this->makeRequest());
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

        $this->downloader->prepare($request);

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            fn (RequestDropped $event) => $event->request->wasDropped() && $event->request->getUri() === $request->getUri()
        );
    }

    public function testDoesNotDispatchEventIfRequestWasNotDropped(): void
    {
        $this->downloader->prepare($this->makeRequest());

        $this->dispatcher->assertNotDispatched(RequestDropped::NAME);
    }

    public function testDispatchesAnEventBeforeRequestIsScheduled(): void
    {
        $request = $this->makeRequest();
        $this->downloader->prepare($request);

        $this->dispatcher->assertDispatched(
            RequestSending::NAME,
            fn (RequestSending $event) => $event->request === $request
        );
    }

    public function testDoesNotScheduleEventIfDroppedByEventListener(): void
    {
        $this->dispatcher->listen(RequestSending::NAME, function (RequestSending $event) {
            $event->request = $event->request->drop('::reason::');
        });
        $request = $this->makeRequest();

        $this->downloader->prepare($request);
        $this->downloader->flush();

        $this->client->assertRequestWasNotSent($request);
    }

    public function testDispatchesAnEventIfRequestWasDroppedByListener(): void
    {
        $this->dispatcher->listen(RequestSending::NAME, function (RequestSending $event) {
            $event->request = $event->request->drop('::reason::');
        });
        $request = $this->makeRequest();

        $this->downloader->prepare($request);

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            fn (RequestDropped $event) => $event->request->getUri() === $request->getUri()
        );
    }
}
