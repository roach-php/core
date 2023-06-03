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

namespace RoachPHP\Tests\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTestCase;

/**
 * @group http
 *
 * @internal
 */
final class RequestTest extends TestCase
{
    use InteractsWithRequestsAndResponses;
    use DroppableTestCase;

    public function testCanAccessTheRequestUri(): void
    {
        $request = $this->makeRequest('::request-uri::');

        self::assertSame('::request-uri::', $request->getUri());
    }

    public function testCanAccessTheRequestUriPath(): void
    {
        $request = $this->makeRequest('https://::request-uri::/::path::');

        self::assertSame('/::path::', $request->getPath());
    }

    public function testCanAddHeader(): void
    {
        $request = $this->makeRequest();

        self::assertFalse($request->hasHeader('X-Custom-Header'));

        $newRequest = $request->addHeader('X-Custom-Header', '::value::');

        self::assertFalse($request->hasHeader('X-Custom-Header'));
        self::assertTrue($newRequest->hasHeader('X-Custom-Header'));
        self::assertSame(['::value::'], $newRequest->getHeader('X-Custom-Header'));
    }

    public function testCanManipulateUnderlyingGuzzleRequest(): void
    {
        $request = $this->makeRequest();

        self::assertFalse($request->hasHeader('X-Custom-Header'));

        $request->withPsrRequest(static function (Request $guzzleRequest) {
            return $guzzleRequest->withHeader('X-Custom-Header', '::value::');
        });

        self::assertTrue($request->hasHeader('X-Custom-Header'));
        self::assertSame(['::value::'], $request->getHeader('X-Custom-Header'));
    }

    public function testCanCallParseCallback(): void
    {
        $called = false;
        $request = $this->makeRequest(callback: static function (Response $response) use (&$called) {
            $called = true;

            yield ParseResult::item(['::item::']);
        });

        $request->callback(
            new Response(new GuzzleResponse(), $request),
        )->next();

        self::assertTrue($called);
    }

    public function testCanAddMetaDataToRequest(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getMeta('::meta-key::'));

        $request = $request->withMeta('::meta-key::', '::meta-value::');
        self::assertSame('::meta-value::', $request->getMeta('::meta-key::'));
    }

    public function testReturnsUnderlyingGuzzleRequest(): void
    {
        $request = $this->makeRequest('::request-uri::');

        self::assertSame('::request-uri::', (string) $request->getPsrRequest()->getUri());
    }

    public function testAddingResponseDoesntMutateRequest(): void
    {
        $requestA = $this->makeRequest('::request-uri::');

        $response = $this->makeResponse($requestA);

        $requestB = $requestA->withResponse($response);

        self::assertNotSame($requestA, $requestB);
        self::assertNull($requestA->getResponse());
        self::assertSame($response, $requestB->getResponse());
    }

    public function testReturnParsedURL(): void
    {
        $request = $this->makeRequest('https://example.com/path#anchor');

        self::assertTrue(
            $request->url->equals('https://example.com/path#anchor'),
        );
    }

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeRequest();
    }
}
