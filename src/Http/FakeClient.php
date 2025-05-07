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

namespace RoachPHP\Http;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Assert;

/**
 * @internal
 */
final class FakeClient implements ClientInterface
{
    /**
     * @var list<string>
     */
    private array $sentRequestUrls = [];

    /**
     * @var array<array-key, Request>
     */ 
    private array $failingRequests = [];

    public function pool(array $requests, ?callable $onFulfilled = null, ?callable $onRejected = null): void
    {
        foreach ($requests as $request) {
            $this->sentRequestUrls[] = $request->getUri();

            if (null !== $onRejected && in_array($request, $this->failingRequests)) {
                $exception = new RequestException($request, new FakeGuzzleException());

                $onRejected($exception);
            }

            if (null !== $onFulfilled) {
                $response = new Response(new GuzzleResponse(), $request);

                $onFulfilled($response);
            }
        }
    }

    public function makeRequestsFail(Request ...$request): static
    {
        $this->failingRequests = $request;

        return $this;
    }

    public function assertRequestWasSent(Request $request): void
    {
        $uri = $request->getUri();

        Assert::assertContains(
            $request->getUri(),
            $this->sentRequestUrls,
            "Expected request to [{$uri}] was not sent",
        );
    }

    public function assertRequestWasNotSent(Request $request): void
    {
        $uri = $request->getUri();

        Assert::assertNotContains(
            $uri,
            $this->sentRequestUrls,
            "Unexpected request sent to [{$uri}]",
        );
    }
}
