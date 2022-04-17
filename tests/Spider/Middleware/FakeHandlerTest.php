<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Spider\Middleware;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Middleware\FakeHandler;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class FakeHandlerTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testReturnsResponseUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());

        $actual = $handler->handleResponse($response);

        self::assertEquals($response, $actual);
    }

    public function testReturnsItemUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $item = new Item(['::key::' => '::value::']);

        $actual = $handler->handleItem($item, $response);

        self::assertEquals($item, $actual);
    }

    public function testReturnsRequestUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest();
        $response = $this->makeResponse($this->makeRequest());

        $actual = $handler->handleRequest($request, $response);

        self::assertEquals($request, $actual);
    }

    public function testCallsConfiguredResponseCallbackIfProvided(): void
    {
        $handler = new FakeHandler(static function (Response $response) {
            return $response->withMeta('::key::', '::value::');
        });
        $response = $this->makeResponse($this->makeRequest());

        $result = $handler->handleResponse($response);

        self::assertSame($result->getMeta('::key::'), '::value::');
    }

    public function testCallsConfiguredItemCallbackIfProvided(): void
    {
        $handler = new FakeHandler(
            null,
            static function (ItemInterface $item, Response $response) {
                return $item->set('::key::', '::new-value::');
            },
        );
        $response = $this->makeResponse($this->makeRequest());
        $item = new Item(['::key::' => '::old-value::']);

        $actual = $handler->handleItem($item, $response);

        self::assertSame('::new-value::', $actual->get('::key::'));
    }

    public function testCallsConfiguredRequestCallbackIfProvided(): void
    {
        $handler = new FakeHandler(
            handleRequestCallback: static fn ($request) => $request->withMeta('::key::', '::value::'),
        );
        $request = $this->makeRequest();
        $response = $this->makeResponse($this->makeRequest());

        $actual = $handler->handleRequest($request, $response);

        self::assertSame('::value::', $actual->getMeta('::key::'));
    }

    public function testAssertResponseHandledPassesWhenHandlerWasCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());

        $handler->handleResponse($response);

        $handler->assertResponseHandled($response);
    }

    public function testAssertResponseHandledFailsWhenHandlerWasNotCalledAtAll(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());

        $this->expectException(AssertionFailedError::class);
        $handler->assertResponseHandled($response);
    }

    public function testAssertResponseHandledFailsWhenHandlerWasNotCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest('::url-a::'));
        $otherResponse = $this->makeResponse($this->makeRequest('::url-b::'));

        $handler->handleResponse($otherResponse);

        $this->expectException(AssertionFailedError::class);
        $handler->assertResponseHandled($response);
    }

    public function testAssertItemHandledPassesWhenHandlerWasCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $item = new Item([]);
        $response = $this->makeResponse($this->makeRequest());

        $handler->handleItem($item, $response);

        $handler->assertItemHandled($item);
    }

    public function testAssertItemHandledFailsWhenHandlerWasNotCalledAtAll(): void
    {
        $handler = new FakeHandler();
        $item = new Item([]);

        $this->expectException(AssertionFailedError::class);
        $handler->assertItemHandled($item);
    }

    public function testAssertItemHandledFailsWhenHandlerWasNotCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $item = new Item(['::key-1::' => '::value-1::']);
        $otherItem = new Item(['::key-2::' => '::value-2::']);
        $response = $this->makeResponse($this->makeRequest());

        $handler->handleItem($otherItem, $response);

        $this->expectException(AssertionFailedError::class);
        $handler->assertItemHandled($item);
    }

    public function testAssertResponseNotHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest('::url-a::'));
        $otherResponse = $this->makeResponse($this->makeRequest('::url-b::'));

        $handler->assertResponseNotHandled($response);

        $handler->handleResponse($otherResponse);
        $handler->assertResponseNotHandled($response);

        $handler->handleResponse($response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertResponseNotHandled($response);
    }

    public function testAssertNoResponseHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());

        $handler->assertNoResponseHandled();

        $handler->handleResponse($response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoResponseHandled();
    }

    public function testNoResultHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $item = new Item(['::key::' => '::value::']);

        $handler->assertNoItemHandled();

        $handler->handleItem($item, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoItemHandled();
    }

    public function testAssertItemNotHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $item = new Item(['::key-1::' => '::value-1::']);
        $otherItem = new Item(['::key-2::' => '::value-2::']);

        $handler->assertItemNotHandled($item);

        $handler->handleItem($otherItem, $response);
        $handler->assertItemNotHandled($item);

        $handler->handleItem($item, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertItemNotHandled($item);
    }

    public function testAssertRequestHandledPassesIfHandlerWasCalledWithCorrectRequest(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest();
        $response = $this->makeResponse($this->makeRequest());

        $handler->handleRequest($request, $response);

        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestHandledFailsWhenHandlerWasNotCalledAtAll(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest('::url-a::');

        $this->expectException(AssertionFailedError::class);
        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestHandledFailsWhenHandlerWasNotCalledWithRequest(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest('::url-a::');
        $otherRequest = $this->makeRequest('::url-b::');
        $response = $this->makeResponse($this->makeRequest());

        $handler->handleRequest($otherRequest, $response);

        $this->expectException(AssertionFailedError::class);
        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestNotHandled(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest('::url-a::');
        $otherRequest = $this->makeRequest('::url-b::');
        $response = $this->makeResponse($this->makeRequest());

        $handler->assertRequestNotHandled($request);

        $handler->handleRequest($otherRequest, $response);
        $handler->assertRequestNotHandled($request);

        $handler->handleRequest($request, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertRequestNotHandled($request);
    }

    public function testAssertNoRequestHandled(): void
    {
        $handler = new FakeHandler();
        $request = $this->makeRequest('::url-a::');
        $response = $this->makeResponse($this->makeRequest());

        $handler->assertNoRequestHandled();

        $handler->handleRequest($request, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoRequestHandled();
    }
}
