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

use Closure;
use Generator;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use RoachPHP\ResponseProcessing\ParseResult;
use RoachPHP\Support\Droppable;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Support\HasMetaData;

final class Request implements DroppableInterface
{
    use HasMetaData;
    use Droppable;

    /**
     * @var Closure(Response): Generator<ParseResult>
     */
    private Closure $parseCallback;

    private GuzzleRequest $guzzleRequest;

    /**
     * @param callable(Response): Generator<ParseResult> $parseMethod
     */
    public function __construct(string $uri, callable $parseMethod, string $method = 'GET')
    {
        $this->guzzleRequest = new GuzzleRequest($method, $uri);
        $this->parseCallback = Closure::fromCallable($parseMethod);
    }

    public function getUri(): string
    {
        return (string) $this->guzzleRequest->getUri();
    }

    public function hasHeader(string $name): bool
    {
        return $this->guzzleRequest->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->guzzleRequest->getHeader($name);
    }

    public function getPath(): string
    {
        return $this->guzzleRequest->getUri()->getPath();
    }

    /**
     * @param string|string[] $value
     */
    public function addHeader(string $name, mixed $value): self
    {
        /** @var GuzzleRequest $request */
        $request = $this->guzzleRequest->withHeader($name, $value);

        $clone = clone $this;
        $clone->guzzleRequest = $request;

        return $clone;
    }

    /**
     * @param callable(GuzzleRequest): GuzzleRequest $callback
     */
    public function withGuzzleRequest(callable $callback): self
    {
        $this->guzzleRequest = $callback($this->guzzleRequest);

        return $this;
    }

    public function callback(Response $response): Generator
    {
        return ($this->parseCallback)($response);
    }

    public function getGuzzleRequest(): GuzzleRequest
    {
        return $this->guzzleRequest;
    }
}
