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

namespace RoachPHP\Http;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Assert;

/**
 * @internal
 */
final class FakeClient implements ClientInterface
{
    /**
     * @var string[]
     */
    private array $sentRequestUrls = [];

    public function pool(array $requests, ?callable $onFulfilled = null, ?callable $onRejected = null): void
    {
        foreach ($requests as $request) {
            $this->sentRequestUrls[] = $request->getUri();

            if ($onFulfilled) {
                $response = new Response(new GuzzleResponse(), $request);

                $onFulfilled($response);
            }
        }
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
