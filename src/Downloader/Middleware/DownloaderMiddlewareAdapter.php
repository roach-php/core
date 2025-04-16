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

namespace RoachPHP\Downloader\Middleware;

use Exception;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;

/**
 * @internal
 */
final class DownloaderMiddlewareAdapter implements DownloaderMiddlewareInterface
{
    private function __construct(
        private RequestMiddlewareInterface|ResponseMiddlewareInterface|ExceptionMiddlewareInterface $middleware,
    ) {
    }

    public static function fromMiddleware(
        RequestMiddlewareInterface|ResponseMiddlewareInterface|ExceptionMiddlewareInterface $middleware,
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

    public function handleException(Exception $exception, Request $request): ?Request
    {
        if ($this->middleware instanceof ExceptionMiddlewareInterface) {
            return $this->middleware->handleException($exception, $request);
        }

        return $request;
    }

    public function configure(array $options): void
    {
        $this->middleware->configure($options);
    }

    public function getMiddleware(): RequestMiddlewareInterface|ResponseMiddlewareInterface|ExceptionMiddlewareInterface
    {
        return $this->middleware;
    }
}
