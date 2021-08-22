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

namespace Sassnowski\Roach\Downloader\Middleware;

use Sassnowski\Roach\Downloader\DownloaderMiddlewareInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

/**
 * @internal
 */
final class DownloaderMiddlewareAdapter implements DownloaderMiddlewareInterface
{
    public function __construct(
        private RequestMiddlewareInterface | ResponseMiddlewareInterface $middleware,
    ) {
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
}
