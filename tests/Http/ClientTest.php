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

namespace RoachPHP\Tests\Http;

use Generator;
use GuzzleHttp;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RoachPHP\Http\Client;
use RoachPHP\Http\RequestException;
use RoachPHP\Http\Response;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class ClientTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testCallFulfilledCallbackForAllSuccessfulResponses(): void
    {
        $client = new Client($this->withMockClient([
            $response1 = new GuzzleHttp\Psr7\Response(200),
            $response2 = new GuzzleHttp\Psr7\Response(202),
            $response3 = new GuzzleHttp\Psr7\Response(204),
        ]));

        /** @var Response[] $responses */
        $responses = [];
        $client->pool([
            $request1 = $this->makeRequest('::uri-1::'),
            $request2 = $this->makeRequest('::uri-2::'),
            $request3 = $this->makeRequest('::uri-3::'),
        ], static function (Response $response) use (&$responses): void {
            $responses[] = $response;
        });

        self::assertCount(3, $responses);
        self::assertSame($response1, $responses[0]->getResponse());
        self::assertSame($request1, $responses[0]->getRequest());
        self::assertSame($response2, $responses[1]->getResponse());
        self::assertSame($request2, $responses[1]->getRequest());
        self::assertSame($response3, $responses[2]->getResponse());
        self::assertSame($request3, $responses[2]->getRequest());
    }

    public function testPassBadResponseExceptionsToFulfilledHandler(): void
    {
        $client = new Client($this->withMockClient([
            $response = new GuzzleHttp\Psr7\Response(400),
        ]));

        /** @var Response[] $responses */
        $responses = [];
        $client->pool(
            [$request = $this->makeRequest('::uri::')],
            static function (Response $response) use (&$responses): void {
                $responses[] = $response;
            },
        );

        self::assertCount(1, $responses);
        self::assertSame($request, $responses[0]->getRequest());
        self::assertSame($response, $responses[0]->getResponse());
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testCallRejectCallbackOnRequestException(string $exceptionClass, callable $makeException): void
    {
        $client = new Client($this->withMockClient([
            static fn (RequestInterface $request) => throw $makeException($request),
        ]));

        $exception = null;
        $client->pool(
            [$request = $this->makeRequest('::uri::')],
            onRejected: static function (RequestException $reason) use (&$exception): void {
                $exception = $reason;
            },
        );

        self::assertInstanceOf(RequestException::class, $exception);
        self::assertInstanceOf($exceptionClass, $exception->getReason());
        self::assertSame('::message::', $exception->getReason()->getMessage());
        self::assertSame($request, $exception->getRequest());
    }

    public function exceptionProvider(): Generator
    {
        yield from [
            'ConnectException' => [
                GuzzleHttp\Exception\ConnectException::class,
                static fn (RequestInterface $request) => new GuzzleHttp\Exception\ConnectException(
                    '::message::',
                    $request,
                ),
            ],
            'TooManyRedirectsException' => [
                GuzzleHttp\Exception\TooManyRedirectsException::class,
                static fn (RequestInterface $request) => new GuzzleHttp\Exception\TooManyRedirectsException(
                    '::message::',
                    $request,
                ),
            ],
        ];
    }

    private function withMockClient(array $handlers): GuzzleHttp\Client
    {
        return new GuzzleHttp\Client([
            'handler' => HandlerStack::create(
                new MockHandler($handlers),
            ),
        ]);
    }
}
