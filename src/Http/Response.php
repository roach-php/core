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
    use Droppable;
    use HasMetaData;

    private ?Crawler $crawler = null;

    public function __construct(
        private ResponseInterface $response,
        private Request $request,
    ) {
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->getCrawler()->{$method}(...$args);
    }

    public function getCrawler(): Crawler
    {
        return $this->crawler ??= new Crawler((string) $this->response->getBody(), (string) $this->request->getUri());
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
        $this->crawler = null;

        return $this;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
