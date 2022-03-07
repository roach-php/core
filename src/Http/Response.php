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

namespace RoachPHP\Http;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use RoachPHP\Support\Droppable;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Support\HasMetaData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @mixin Crawler
 */
final class Response implements DroppableInterface
{
    use HasMetaData;
    use Droppable;

    private Crawler $crawler;

    public function __construct(private ResponseInterface $response, private Request $request)
    {
        $this->crawler = new Crawler((string) $response->getBody(), $request->getUri());
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->crawler->{$method}(...$args);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getStatus(): int
    {
        return $this->response->getStatusCode();
    }

    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }

    public function withBody(string $body): self
    {
        $this->response = $this->response->withBody(Utils::streamFor($body));
        $this->crawler = new Crawler($body, $this->request->getUri());

        return $this;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
