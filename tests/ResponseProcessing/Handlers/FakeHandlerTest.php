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

namespace Sassnowski\Roach\Tests\ResponseProcessing\Handlers;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ResponseProcessing\Handlers\FakeHandler;
use Sassnowski\Roach\Tests\InteractsWithRequests;
use Sassnowski\Roach\Tests\InteractsWithResponses;

/**
 * @internal
 */
final class FakeHandlerTest extends TestCase
{
    use InteractsWithRequests;
    use InteractsWithResponses;

    public function testReturnsResponseUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());

        $actual = $handler->handleResponse($response);

        self::assertEquals($response, $actual);
    }

    public function testReturnsItemUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());
        $item = new Item(['::key::' => '::value::']);

        $actual = $handler->handleItem($item, $response);

        self::assertEquals($item, $actual);
    }

    public function testReturnsRequestUnchangedByDefault(): void
    {
        $handler = new FakeHandler();
        $request = $this->createRequest();
        $response = $this->makeResponse($this->createRequest());

        $actual = $handler->handleRequest($request, $response);

        self::assertEquals($request, $actual);
    }

    public function testCallsConfiguredResponseCallbackIfProvided(): void
    {
        $handler = new FakeHandler(static function (Response $response) {
            return $response->withMeta('::key::', '::value::');
        });
        $response = $this->makeResponse($this->createRequest());

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
        $response = $this->makeResponse($this->createRequest());
        $item = new Item(['::key::' => '::old-value::']);

        $actual = $handler->handleItem($item, $response);

        self::assertSame('::new-value::', $actual->get('::key::'));
    }

    public function testCallsConfiguredRequestCallbackIfProvided(): void
    {
        $handler = new FakeHandler(
            handleRequestCallback: static fn ($request) => $request->withMeta('::key::', '::value::'),
        );
        $request = $this->createRequest();
        $response = $this->makeResponse($this->createRequest());

        $actual = $handler->handleRequest($request, $response);

        self::assertSame('::value::', $actual->getMeta('::key::'));
    }

    public function testAssertResponseHandledPassesWhenHandlerWasCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());

        $handler->handleResponse($response);

        $handler->assertResponseHandled($response);
    }

    public function testAssertResponseHandledFailsWhenHandlerWasNotCalledAtAll(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());

        $this->expectException(AssertionFailedError::class);
        $handler->assertResponseHandled($response);
    }

    public function testAssertResponseHandledFailsWhenHandlerWasNotCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest('::url-a::'));
        $otherResponse = $this->makeResponse($this->createRequest('::url-b::'));

        $handler->handleResponse($otherResponse);

        $this->expectException(AssertionFailedError::class);
        $handler->assertResponseHandled($response);
    }

    public function testAssertItemHandledPassesWhenHandlerWasCalledWithResponse(): void
    {
        $handler = new FakeHandler();
        $item = new Item([]);
        $response = $this->makeResponse($this->createRequest());

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
        $response = $this->makeResponse($this->createRequest());

        $handler->handleItem($otherItem, $response);

        $this->expectException(AssertionFailedError::class);
        $handler->assertItemHandled($item);
    }

    public function testAssertResponseNotHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest('::url-a::'));
        $otherResponse = $this->makeResponse($this->createRequest('::url-b::'));

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
        $response = $this->makeResponse($this->createRequest());

        $handler->assertNoResponseHandled();

        $handler->handleResponse($response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoResponseHandled();
    }

    public function testNoResultHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());
        $item = new Item(['::key::' => '::value::']);

        $handler->assertNoItemHandled();

        $handler->handleItem($item, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoItemHandled();
    }

    public function testAssertItemNotHandled(): void
    {
        $handler = new FakeHandler();
        $response = $this->makeResponse($this->createRequest());
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
        $request = $this->createRequest();
        $response = $this->makeResponse($this->createRequest());

        $handler->handleRequest($request, $response);

        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestHandledFailsWhenHandlerWasNotCalledAtAll(): void
    {
        $handler = new FakeHandler();
        $request = $this->createRequest('::url-a::');

        $this->expectException(AssertionFailedError::class);
        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestHandledFailsWhenHandlerWasNotCalledWithRequest(): void
    {
        $handler = new FakeHandler();
        $request = $this->createRequest('::url-a::');
        $otherRequest = $this->createRequest('::url-b::');
        $response = $this->makeResponse($this->createRequest());

        $handler->handleRequest($otherRequest, $response);

        $this->expectException(AssertionFailedError::class);
        $handler->assertRequestHandled($request);
    }

    public function testAssertRequestNotHandled(): void
    {
        $handler = new FakeHandler();
        $request = $this->createRequest('::url-a::');
        $otherRequest = $this->createRequest('::url-b::');
        $response = $this->makeResponse($this->createRequest());

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
        $request = $this->createRequest('::url-a::');
        $response = $this->makeResponse($this->createRequest());

        $handler->assertNoRequestHandled();

        $handler->handleRequest($request, $response);
        $this->expectException(AssertionFailedError::class);
        $handler->assertNoRequestHandled();
    }
}
