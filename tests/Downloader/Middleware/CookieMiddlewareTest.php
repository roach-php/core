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

namespace RoachPHP\Tests\Downloader\Middleware;

use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\CookieMiddleware;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class CookieMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testAddCookiesForDomainToOutgoingRequests(): void
    {
        $jar = CookieJar::fromArray([
            'cookie-key' => '::cookie-value::',
        ], 'example.com');
        $middleware = new CookieMiddleware($jar);
        $request = $this->makeRequest('http://example.com');

        $processedRequest = $middleware->handleRequest($request);

        self::assertTrue($processedRequest->getGuzzleRequest()->hasHeader('Cookie'));
        self::assertSame(
            'cookie-key=::cookie-value::',
            $processedRequest->getGuzzleRequest()->getHeaderLine('Cookie'),
        );
    }

    public function testDontAddCookiesForDifferentDomain(): void
    {
        $jar = CookieJar::fromArray([
            'cookie-key' => '::cookie-value::',
        ], 'different-domain.com');
        $middleware = new CookieMiddleware($jar);
        $request = $this->makeRequest('http://example.com');

        $processedRequest = $middleware->handleRequest($request);

        self::assertFalse($processedRequest->getGuzzleRequest()->hasHeader('Cookie'));
    }

    public function testStoreResponseCookiesInCookieJar(): void
    {
        $request = $this->makeRequest('http://example.com');
        $response = $this->makeResponse(
            $request,
            headers: ['Set-Cookie' => ['cookie-name=::cookie-value::; Domain=example.com']],
        );
        $jar = new CookieJar();
        $middleware = new CookieMiddleware($jar);

        $middleware->handleResponse($response);

        self::assertSame(1, $jar->count());
        self::assertSame('example.com', $jar->toArray()[0]['Domain']);
        self::assertSame('cookie-name', $jar->toArray()[0]['Name']);
        self::assertSame('::cookie-value::', $jar->toArray()[0]['Value']);
    }
}
