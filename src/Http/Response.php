<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Http;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @mixin Crawler
 */
final class Response
{
    private Crawler $crawler;

    public function __construct(private GuzzleResponse $response, Request $request)
    {
        $this->crawler = new Crawler((string) $response->getBody(), (string) $request->getUri());
    }

    public function __call(string $method, array $args)
    {
        return $this->crawler->{$method}(...$args);
    }

    public function getStatus(): int
    {
        return $this->response->getStatusCode();
    }

    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }
}
