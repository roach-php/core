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

namespace RoachPHP\Tests\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTest;

/**
 * @group http
 *
 * @internal
 */
final class RequestTest extends TestCase
{
    use InteractsWithRequestsAndResponses;
    use DroppableTest;

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

        $request->withGuzzleRequest(static function (Request $guzzleRequest) {
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

        self::assertSame('::request-uri::', (string) $request->getGuzzleRequest()->getUri());
    }

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeRequest();
    }
}
