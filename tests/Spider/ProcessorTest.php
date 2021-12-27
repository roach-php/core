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

namespace RoachPHP\Tests\Spider;

use Closure;
use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\ResponseDropped;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Spider\Middleware\FakeHandler;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\Processor;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class ProcessorTest extends TestCase
{
    use InteractsWithRequestsAndResponses;
    private Processor $processor;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
        $this->processor = new Processor($this->dispatcher);
    }

    public function testCallsCallbackOnRequest(): void
    {
        $parseCallback = static fn () => null;
        $expectedRequest = ParseResult::request('GET', '::new-url::', $parseCallback);
        $request = $this->makeRequest(callback: static fn () => yield $expectedRequest);
        $response = $this->makeResponse($request);

        $result = \iterator_to_array($this->processor->handle($response));

        self::assertEquals([$expectedRequest], $result);
    }

    public function testCallsHandlersForIncomingResponses(): void
    {
        $handler = $this->makeHandler();
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item([]));
        $response = $this->makeResponse($request);

        $this->processor
            ->withMiddleware($handler)
            ->handle($response)
            ->next();

        $handler->assertResponseHandled($response);
    }

    public function testDoesNotPassOnResponseIfDroppedByHandler(): void
    {
        $dropHandler = $this->makeHandler(handleResponse: static fn ($response) => $response->drop('::reason::'));
        $otherHandler = $this->makeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $stack = $this->processor->withMiddleware($dropHandler, $otherHandler);

        $result = \iterator_to_array($stack->handle($response));

        self::assertEmpty($result);
        $otherHandler->assertNoResponseHandled();
    }

    public function testCallResponseHandlersInOrder(): void
    {
        $handlerA = $this->makeHandler(static function (Response $response) {
            return $response->withMeta('foo', $response->getMeta('foo') . 'A');
        });
        $handlerB = $this->makeHandler(static function (Response $response) {
            return $response->withMeta('foo', $response->getMeta('foo') . 'B');
        });
        $request = $this->makeRequest(callback: static function (Response $response) {
            self::assertEquals('AB', $response->getMeta('foo'));

            yield ParseResult::item([]);
        });

        $this->processor
            ->withMiddleware($handlerA, $handlerB)
            ->handle($this->makeResponse($request))
            ->next();
    }

    public function testPassesEachNewRequestToHandlersInOrder(): void
    {
        $handlerA = $this->makeHandler(
            handleRequestCallback: static fn ($r) => $r->withMeta('::key::', $r->getMeta('::key::', '') . 'A'),
        );
        $handlerB = $this->makeHandler(
            handleRequestCallback: static fn ($r) => $r->withMeta('::key::', $r->getMeta('::key::', '') . 'B'),
        );
        $results = [
            ParseResult::request('GET', '::url::', static fn () => null),
            ParseResult::request('GET', '::url::', static fn () => null),
        ];
        $request = $this->makeRequest(callback: static fn () => yield from $results);
        $stack = $this->processor->withMiddleware($handlerA, $handlerB);

        $actual = \iterator_to_array($stack->handle($this->makeResponse($request)));

        self::assertSame('AB', $actual[0]->value()->getMeta('::key::'));
        self::assertSame('AB', $actual[1]->value()->getMeta('::key::'));
    }

    public function testDoesNotPassOnRequestIfDroppedByHandler(): void
    {
        $dropHandler = $this->makeHandler(handleRequestCallback: static function ($request, $response) {
            return $request->drop('::reason::');
        });
        $handlerB = $this->makeHandler();
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );
        $stack = $this->processor->withMiddleware($dropHandler, $handlerB);

        $result = \iterator_to_array($stack->handle($this->makeResponse($request)));

        $handlerB->assertNoRequestHandled();
        self::assertEmpty($result);
    }

    public function testCallsItemHandlersInOrderForOutgoingItems(): void
    {
        $handlerA = $this->makeHandler(
            handleItemCallback: static fn ($item) => $item->set('::key::', $item->get('::key::', '') . 'A'),
        );
        $handlerB = $this->makeHandler(
            handleItemCallback: static fn ($item) => $item->set('::key::', $item->get('::key::', '') . 'B'),
        );
        $request = $this->makeRequest(callback: static function (Response $response) {
            yield ParseResult::item([]);
        });

        $result = $this->processor
            ->withMiddleware($handlerA, $handlerB)
            ->handle($this->makeResponse($request))
            ->current();

        self::assertSame('AB', $result->value()->get('::key::'));
    }

    public function testDoesNotPassOnItemIfDroppedByHandler(): void
    {
        $dropHandler = $this->makeHandler(handleItemCallback: static function ($item, $response) {
            return $item->drop('::reason::');
        });
        $handlerB = $this->makeHandler();
        $item = new Item([]);
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::fromValue($item));
        $stack = $this->processor->withMiddleware($dropHandler, $handlerB);

        $result = \iterator_to_array($stack->handle($this->makeResponse($request)));

        $handlerB->assertNoItemHandled();
        self::assertEmpty($result);
    }

    public function testDispatchesEventIfResponseWasDropped(): void
    {
        $dropHandler = $this->makeHandler(handleResponse: static fn ($response) => $response->drop('::reason::'));
        $otherHandler = $this->makeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $stack = $this->processor->withMiddleware($dropHandler, $otherHandler);

        \iterator_to_array($stack->handle($response));

        $this->dispatcher->assertDispatched(ResponseDropped::NAME);
    }

    public function testDoesNotDispatchEventIfResponseWasNotDropped(): void
    {
        $this->processor->handle($this->makeResponse())->next();

        $this->dispatcher->assertNotDispatched(ResponseDropped::NAME);
    }

    public function testDispatchEventIfRequestWasDropped(): void
    {
        $dropHandler = $this->makeHandler(handleRequestCallback: static function ($request, $response) {
            return $request->drop('::reason::');
        });
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );

        $this->processor
            ->withMiddleware($dropHandler)
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            static fn (RequestDropped $event) => $event->request->getDropReason() === '::reason::',
        );
    }

    public function testDontDispatchEventIfRequestWasNotDropped(): void
    {
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );

        $this->processor
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertNotDispatched(RequestDropped::NAME);
    }

    public function testDispatchEventIfItemWasDropped(): void
    {
        $dropHandler = $this->makeHandler(handleItemCallback: static function ($item) {
            return $item->drop('::reason::');
        });
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item(['foo' => 'bar']));

        $this->processor
            ->withMiddleware($dropHandler)
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertDispatched(
            ItemDropped::NAME,
            static fn (ItemDropped $event) => $event->item->all() === ['foo' => 'bar'],
        );
    }

    public function testDontDispatchEventIfItemWasNotDropped(): void
    {
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item(['foo' => 'bar']));

        $this->processor
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertNotDispatched(ItemDropped::NAME);
    }

    private function makeHandler(
        ?Closure $handleResponse = null,
        ?Closure $handleItemCallback = null,
        ?Closure $handleRequestCallback = null,
    ): FakeHandler {
        return new FakeHandler($handleResponse, $handleItemCallback, $handleRequestCallback);
    }
}
