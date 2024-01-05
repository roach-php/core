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

namespace RoachPHP\Tests\Downloader;

use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Downloader\Middleware\DownloaderMiddlewareAdapter;
use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Downloader\Middleware\ResponseMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class DownloaderMiddlewareAdapterTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testDontWrapMiddlewareIfItAlreadyImplementsFullInterface(): void
    {
        $middleware = new class() implements DownloaderMiddlewareInterface {
            use Configurable;

            public function handleRequest(Request $request): Request
            {
                return $request;
            }

            public function handleResponse(Response $response): Response
            {
                return $response;
            }
        };

        $class = DownloaderMiddlewareAdapter::fromMiddleware($middleware);

        self::assertNotInstanceOf(DownloaderMiddlewareAdapter::class, $middleware);
        self::assertSame($middleware, $class);
    }

    /**
     * @dataProvider requestMiddlewareProvider
     */
    public function testRequestMiddlewareImplementation(callable $testCase): void
    {
        $middleware = new class() implements RequestMiddlewareInterface {
            use Configurable;

            public function handleRequest(Request $request): Request
            {
                return $request->withMeta('::key::', '::value::');
            }
        };
        $adapter = DownloaderMiddlewareAdapter::fromMiddleware($middleware);

        $testCase($adapter);
    }

    public static function requestMiddlewareProvider(): iterable
    {
        yield 'return response unchanged' => [static function (DownloaderMiddlewareAdapter $adapter): void {
            $response = self::makeResponse();

            $result = $adapter->handleResponse($response);

            self::assertSame($response, $result);
        }];

        yield 'call middleware for requests' => [static function (DownloaderMiddlewareAdapter $adapter): void {
            $request = self::makeRequest();

            $result = $adapter->handleRequest($request);

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
        $adapter = DownloaderMiddlewareAdapter::fromMiddleware($middleware);

        $testCase($adapter);
    }

    public static function responseMiddlewareProvider(): iterable
    {
        yield 'return request unchanged' => [static function (DownloaderMiddlewareAdapter $adapter): void {
            $request = self::makeRequest();

            $result = $adapter->handleRequest($request);

            self::assertSame($request, $result);
        }];

        yield 'call middleware for responses' => [static function (DownloaderMiddlewareAdapter $adapter): void {
            $response = self::makeResponse();

            $result = $adapter->handleResponse($response);

            self::assertSame('::value::', $result->getMeta('::key::'));
        }];
    }
}
