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

namespace RoachPHP\Spider\Middleware;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;

/**
 * @internal
 */
final class MiddlewareAdapter implements MiddlewareInterface
{
    private function __construct(
        private RequestMiddlewareInterface|ItemMiddlewareInterface|ResponseMiddlewareInterface $middleware,
    ) {
    }

    public static function fromMiddleware(
        RequestMiddlewareInterface|ItemMiddlewareInterface|ResponseMiddlewareInterface $middleware
    ): MiddlewareInterface {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        return new self($middleware);
    }

    public function handleItem(ItemInterface $item, Response $response): ItemInterface
    {
        if ($this->middleware instanceof ItemMiddlewareInterface) {
            return $this->middleware->handleItem($item, $response);
        }

        return $item;
    }

    public function handleRequest(Request $request, Response $response): Request
    {
        if ($this->middleware instanceof RequestMiddlewareInterface) {
            return $this->middleware->handleRequest($request, $response);
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
