<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Spider\Middleware;

use Generator;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Middleware\ItemMiddlewareInterface;
use RoachPHP\Spider\Middleware\RequestMiddlewareInterface;
use RoachPHP\Spider\Middleware\ResponseMiddlewareInterface;
use RoachPHP\Spider\Middleware\SpiderMiddlewareAdapter;
use RoachPHP\Spider\SpiderMiddlewareInterface;
use RoachPHP\Support\Configurable;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class SpiderMiddlewareAdapterTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testDontDecorateClassIfItAlreadyImplementsTheFullInterface(): void
    {
        $middleware = new class() implements SpiderMiddlewareInterface {
            use Configurable;

            public function handleItem(ItemInterface $item, Response $response): ItemInterface
            {
                return $item;
            }

            public function handleRequest(Request $request, Response $response): Request
            {
                return $request;
            }

            public function handleResponse(Response $response): Response
            {
                return $response;
            }
        };

        $class = SpiderMiddlewareAdapter::fromMiddleware($middleware);

        self::assertNotInstanceOf(SpiderMiddlewareAdapter::class, $class);
        self::assertSame($middleware, $class);
    }

    /**
     * @dataProvider itemMiddlewareProvider
     */
    public function testItemMiddlewareImplementation(callable $testCase): void
    {
        $middleware = new class() implements ItemMiddlewareInterface {
            use Configurable;

            public function handleItem(ItemInterface $item, Response $response): ItemInterface
            {
                return $item->set('::key::', '::value::');
            }
        };
        $adapter = SpiderMiddlewareAdapter::fromMiddleware($middleware);

        $testCase($adapter);
    }

    public function itemMiddlewareProvider(): Generator
    {
        yield 'return request unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'return response unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'call middleware function for items' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest());
            $item = new Item([]);

            $result = $adapter->handleItem($item, $response);

            self::assertEquals(['::key::' => '::value::'], $result->all());
        }];
    }

    /**
     * @dataProvider requestMiddlewareProvider
     */
    public function testRequestMiddlewareImplementation(callable $testCase): void
    {
        $middleware = new class() implements RequestMiddlewareInterface {
            use Configurable;

            public function handleRequest(Request $request, Response $response): Request
            {
                return $request->withMeta('::key::', '::value::');
            }
        };
        $adapter = SpiderMiddlewareAdapter::fromMiddleware($middleware);

        $testCase($adapter);
    }

    public function requestMiddlewareProvider(): Generator
    {
        yield 'return response unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleResponse($response);

            self::assertEquals($response, $result);
        }];

        yield 'return item unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'call middleware function for requests' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }

    /**
     * @dataProvider responseMiddlewareProvider
     */
    public function testResponseMiddlewareImplementation(callable $testCase): void
    {
        $middleware = new class() implements ResponseMiddlewareInterface {
            use Configurable;

            public function handleResponse(Response $response): Response
            {
                return $response->withMeta('::key::', '::value::');
            }
        };
        $adapter = SpiderMiddlewareAdapter::fromMiddleware($middleware);

        $testCase($adapter);
    }

    public function responseMiddlewareProvider(): Generator
    {
        yield 'return item unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $item = new Item(['::key::' => '::value::']);
            $response = $this->makeResponse($this->makeRequest());

            $result = $adapter->handleItem($item, $response);

            self::assertSame(['::key::' => '::value::'], $result->all());
        }];

        yield 'return request unchanged' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));
            $request = $this->makeRequest('::url-b::');

            $result = $adapter->handleRequest($request, $response);

            self::assertEquals($request, $result);
        }];

        yield 'call middleware function for responses' => [function (SpiderMiddlewareAdapter $adapter): void {
            $response = $this->makeResponse($this->makeRequest('::url-a::'));

            $result = $adapter->handleResponse($response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }
}
