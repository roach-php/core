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

namespace Sassnowski\Roach\Tests\Parsing\Handlers;

use Generator;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\Parsing\Handlers\Handler;
use Sassnowski\Roach\Parsing\Handlers\HandlerAdapter;
use Sassnowski\Roach\Parsing\ItemHandlerInterface;
use Sassnowski\Roach\Parsing\RequestHandlerInterface;
use Sassnowski\Roach\Parsing\ResponseHandlerInterface;
use Sassnowski\Roach\Tests\InteractsWithRequests;
use Sassnowski\Roach\Tests\InteractsWithResponses;

/**
 * @internal
 */
final class HandlerAdapterTest extends TestCase
{
    use InteractsWithResponses;
    use InteractsWithRequests;

    /**
     * @dataProvider itemHandlerProvider
     */
    public function testItemHandlerImplementation(callable $testCase): void
    {
        $handler = new class() extends Handler implements ItemHandlerInterface {
            public function handleItem(ItemInterface $item, Response $response, ): ItemInterface
            {
                return $item->set('::key::', '::value::');
            }
        };
        $adapter = new HandlerAdapter($handler);

        $testCase($adapter);
    }

    public function itemHandlerProvider(): Generator
    {
        yield 'return request unchanged' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest('::url-a::'));
            $request = $this->createRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'return response unchanged' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'call handler function for items' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest());
            $item = new Item([]);

            $result = $adapter->handleItem($item, $response);

            self::assertEquals(['::key::' => '::value::'], $result->all());
        }];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testRequestHandlerImplementation(callable $testCase): void
    {
        $handler = new class() extends Handler implements RequestHandlerInterface {
            public function handleRequest(Request $request, Response $response): Request
            {
                return $request->withMeta('::key::', '::value::');
            }
        };
        $adapter = new HandlerAdapter($handler);

        $testCase($adapter);
    }

    public function requestHandlerProvider(): Generator
    {
        yield 'return response unchanged' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest());

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'return item unchanged' => [function (HandlerAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->createRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'call handler function for requests' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest('::url-a::'));
            $request = $this->createRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }

    /**
     * @dataProvider responseHandlerProvider
     */
    public function testResponseHandlerImplementation(callable $testCase): void
    {
        $handler = new class() extends Handler implements ResponseHandlerInterface {
            public function handleResponse(Response $response): Response
            {
                return $response->withMeta('::key::', '::value::');
            }
        };
        $adapter = new HandlerAdapter($handler);

        $testCase($adapter);
    }

    public function responseHandlerProvider(): Generator
    {
        yield 'return item unchanged' => [function (HandlerAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->createRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'return request unchanged' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest('::url-a::'));
            $request = $this->createRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'call handler function for responses' => [function (HandlerAdapter $adapter): void {
            $response = $this->makeResponse($this->createRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }
}
