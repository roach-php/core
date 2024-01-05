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

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTestCase;

/**
 * @internal
 */
final class ResponseTest extends TestCase
{
    use DroppableTestCase;
    use InteractsWithRequestsAndResponses;

    public function testCanAccessDomCrawlerDirectlyFromResponse(): void
    {
        $response = $this->makeResponse(body: '<html lang="en"><body><a href="https://roach-php.dev">Docs</a></body></html>');

        $links = $response->filter('a')->links();

        self::assertCount(1, $links);
    }

    /**
     * @dataProvider responseCodeProvider
     */
    public function testCanRetrieveStatusCodeOfOriginalResponse(int $statusCode): void
    {
        $response = new Response(new \GuzzleHttp\Psr7\Response($statusCode), $this->makeRequest());

        self::assertSame($statusCode, $response->getStatus());
    }

    /**
     * @dataProvider responseBodyProvider
     */
    public function testCanRetrieveHtmlBodyOfOriginalResponse(callable $getBody): void
    {
        $body = '<html lang="en"><body><p>Hello, world!</p></body>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $getBody($body)),
            $this->makeRequest(),
        );

        self::assertSame($body, $response->getBody());
    }

    public function testCanUpdateResponseBody(): void
    {
        $originalBody = '<html lang="en"><body><p>Old</p></body></html>';
        $newBody = '<html lang="en"><body><p>New</p></body></html>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $originalBody),
            $this->makeRequest(),
        );

        $response = $response->withBody($newBody);

        self::assertSame($newBody, $response->getBody());
    }

    public function testUpdatingResponseBodyUpdatesCrawler(): void
    {
        $originalBody = '<html lang="en"><body><p>Old</p></body></html>';
        $newBody = '<html lang="en"><body><p>New</p></body></html>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $originalBody),
            $this->makeRequest(),
        );

        $response = $response->withBody($newBody);

        self::assertSame('New', $response->filter('p')->text(''));
    }

    public static function responseCodeProvider(): iterable
    {
        yield from [
            [200],
            [300],
            [301],
            [302],
            [400],
            [404],
            [500],
        ];
    }

    public static function responseBodyProvider(): iterable
    {
        yield from [
            'string' => [static fn (string $body) => $body],

            'stream' => [static function (string $body) {
                $stream = \fopen('php://memory', 'r+b');
                \fwrite($stream, $body);
                \rewind($stream);

                return $stream;
            }],

            'StreamInterface' => [static function (string $body) {
                $stream = \fopen('php://memory', 'r+b');
                \fwrite($stream, $body);
                \rewind($stream);

                return new Stream($stream);
            }],
        ];
    }

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeResponse(
            $this->makeRequest(),
        );
    }
}
