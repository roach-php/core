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

use Closure;
use Generator;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\RequestInterface;

final class Request
{
    private Closure $parseCallback;

    private RequestInterface $guzzleRequest;

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
        $this->guzzleRequest = $this->guzzleRequest->withHeader($name, $value);

        return $this;
    }

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
