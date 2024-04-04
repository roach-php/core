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

namespace RoachPHP\Tests\Downloader\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\HttpErrorMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

/**
 * @internal
 */
final class HttpErrorMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private HttpErrorMiddleware $middleware;

    private FakeLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger();
        $this->middleware = new HttpErrorMiddleware($this->logger);
    }

    public static function successfulHTTPStatus(): \Generator
    {
        yield from [
            [200],
            [201],
            [202],
            [203],
            [204],
            [205],
            [206],
            [207],
            [208],
        ];
    }

    public static function unsuccessfulHTTPStatus(): \Generator
    {
        yield from [
            [100],
            [101],
            [102],
            [103],
            [300],
            [301],
            [302],
            [303],
            [304],
            [305],
            [306],
            [307],
            [308],
            [400],
            [401],
            [402],
            [403],
            [404],
            [405],
            [406],
            [407],
            [408],
            [409],
            [410],
            [411],
            [412],
            [413],
            [414],
            [415],
            [416],
            [417],
            [418],
            [421],
            [422],
            [423],
            [424],
            [425],
            [426],
            [428],
            [429],
            [431],
            [451],
            [500],
            [501],
            [502],
            [503],
            [504],
            [505],
            [506],
            [507],
            [508],
            [510],
            [511],
        ];
    }

    #[DataProvider('successfulHTTPStatus')]
    public function testAllowResponseWithSuccessfulHTTPStatus(int $status): void
    {
        $response = $this->makeResponse(status: $status);
        $this->middleware->configure([]);

        $result = $this->middleware->handleResponse($response);

        self::assertSame($result, $response);
        self::assertFalse($result->wasDropped());
    }

    #[DataProvider('unsuccessfulHTTPStatus')]
    public function testDropResponseWithNonSuccessfulHTTPStatus(int $status): void
    {
        $response = $this->makeResponse(status: $status);
        $this->middleware->configure([]);

        $result = $this->middleware->handleResponse($response);

        self::assertNotSame($result, $response);
        self::assertTrue($result->wasDropped());
    }

    public function testLogDroppedResponses(): void
    {
        $request = $this->makeRequest('https://example.com');
        $response = $this->makeResponse(request: $request, status: 400);
        $this->middleware->configure([]);

        $this->middleware->handleResponse($response);

        self::assertTrue(
            $this->logger->messageWasLogged(
                'info',
                '[HttpErrorMiddleware] Dropping unsuccessful response',
                ['uri' => 'https://example.com', 'status' => 400],
            ),
        );
    }

    public function testDontLogAllowedResponses(): void
    {
        $response = $this->makeResponse(status: 200);
        $this->middleware->configure([]);

        $this->middleware->handleResponse($response);

        self::assertFalse(
            $this->logger->messageWasLogged(
                'info',
                '[AllowedHttpStatusMiddleware] Dropping response with unallowed HTTP status',
            ),
        );
    }

    public function testAllowResponsesWithCustomAllowedStatuses(): void
    {
        $response = $this->makeResponse(status: 404);
        $this->middleware->configure(['handleStatus' => [404]]);

        $result = $this->middleware->handleResponse($response);

        self::assertSame($result, $response);
        self::assertFalse($result->wasDropped());
    }
}
