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

namespace RoachPHP\Downloader\Middleware;

use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;

/**
 * @internal
 */
final class DownloaderMiddlewareAdapter implements DownloaderMiddlewareInterface
{
    private function __construct(
        private RequestMiddlewareInterface|ResponseMiddlewareInterface $middleware,
    ) {
    }

    public static function fromMiddleware(
        RequestMiddlewareInterface|ResponseMiddlewareInterface $middleware,
    ): DownloaderMiddlewareInterface {
        if ($middleware instanceof DownloaderMiddlewareInterface) {
            return $middleware;
        }

        return new self($middleware);
    }

    public function handleRequest(Request $request): Request
    {
        if ($this->middleware instanceof RequestMiddlewareInterface) {
            return $this->middleware->handleRequest($request);
        }

        return $request;
    }

    public function handleResponse(Response $response): Response
    {
        if ($this->middleware instanceof ResponseMiddlewareInterface) {
            return $this->middleware->handleResponse($response);
        }

        return $response;
    }

    public function configure(array $options): void
    {
        $this->middleware->configure($options);
    }

    public function getMiddleware(): RequestMiddlewareInterface|ResponseMiddlewareInterface
    {
        return $this->middleware;
    }
}
