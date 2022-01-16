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

use Generator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use RoachPHP\Exception\Exception;

final class Client implements ClientInterface
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient();
    }

    /**
     * @param Request[] $requests
     */
    public function pool(array $requests, ?callable $onFulfilled = null, ?callable $onRejected = null): void
    {
        $makeRequests = function () use ($requests): Generator {
            foreach ($requests as $request) {
                yield function () use ($request) {
                    return $this->client
                        ->sendAsync($request->getPsrRequest(), $request->getOptions())
                        ->then(
                            static fn (ResponseInterface $response) => new Response($response, $request),
                            static fn (GuzzleException $e) => throw new Exception(
                                $request,
                                $e->getMessage(),
                                $e->getCode(),
                                $e
                            ),
                        );
                };
            }
        };

        $pool = new Pool($this->client, $makeRequests(), [
            'concurrency' => 0,
            'fulfilled' => $onFulfilled,
            'rejected'  => $onRejected,
        ]);

        $pool->promise()->wait();
    }
}
