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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\FakeClient;
use RoachPHP\Http\Response;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class FakeClientTest extends TestCase
{
    use InteractsWithRequestsAndResponses;
    private FakeClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeClient();
    }

    public function testAssertRequestWasSent(): void
    {
        $requestA = $this->makeRequest('::url-a::');
        $requestB = $this->makeRequest('::url-b::');
        $requestC = $this->makeRequest('::url-c::');

        $this->client->pool([$requestA, $requestB]);

        $this->client->assertRequestWasSent($requestA);
        $this->client->assertRequestWasSent($requestB);

        $this->expectException(AssertionFailedError::class);
        $this->client->assertRequestWasSent($requestC);
    }

    public function testAssertRequestWasNotSent(): void
    {
        $requestA = $this->makeRequest('::url-a::');
        $requestB = $this->makeRequest('::url-b::');
        $requestC = $this->makeRequest('::url-c::');

        $this->client->pool([$requestC]);

        $this->client->assertRequestWasNotSent($requestA);
        $this->client->assertRequestWasNotSent($requestB);

        $this->expectException(AssertionFailedError::class);
        $this->client->assertRequestWasNotSent($requestC);
    }

    public function testCallOnFulfilledCallbackWithResponseForEachRequest(): void
    {
        $requests = [
            $this->makeRequest('::url-a::')->withMeta('index', 0),
            $this->makeRequest('::url-b::')->withMeta('index', 1),
            $this->makeRequest('::url-c::')->withMeta('index', 2),
        ];

        $this->client->pool($requests, static function (Response $response) use (&$requests): void {
            self::assertContains($response->getRequest(), $requests);

            // Remove request from array so it can't be used for
            // another reponse as well.
            unset($requests[$response->getRequest()->getMeta('index')]);
        });

        self::assertEmpty($requests);
    }
}
