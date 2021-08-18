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

namespace Sassnowski\Roach\Http;

use Generator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;

final class Client implements ClientInterface
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient();
    }

    public function dispatch(Request $request): PromiseInterface
    {
        return $this->client->sendAsync($request->getGuzzleRequest());
    }

    public function pool(Generator $requests, int $concurrency = 5): PromiseInterface
    {
        $pool = new Pool($this->client, $requests, ['concurrency' => $concurrency]);

        return $pool->promise();
    }
}
