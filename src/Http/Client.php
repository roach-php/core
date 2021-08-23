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

namespace RoachPHP\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;

final class Client implements ClientInterface
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient();
    }

    public function pool(array $requests, ?callable $onFulfilled = null): void
    {
        $makeRequests = function () use ($requests) {
            foreach ($requests as $request) {
                yield function () use ($request) {
                    return $this->client
                        ->sendAsync($request->getGuzzleRequest())
                        ->then(static fn (ResponseInterface $response) => new Response($response, $request));
                };
            }
        };

        $pool = new Pool($this->client, $makeRequests(), [
            'concurrency' => 0,
            'fulfilled' => $onFulfilled,
        ]);

        $pool->promise()->wait();
    }
}
