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

namespace RoachPHP\ResponseProcessing\Handlers;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ResponseProcessing\Handlers\ItemHandlerInterface;
use RoachPHP\ResponseProcessing\MiddlewareInterface;
use RoachPHP\ResponseProcessing\Handlers\RequestHandlerInterface;
use RoachPHP\ResponseProcessing\Handlers\ResponseHandlerInterface;

final class HandlerAdapter implements MiddlewareInterface
{
    public function __construct(
        private RequestHandlerInterface | ItemHandlerInterface | ResponseHandlerInterface $handler,
    ) {
    }

    public function handleItem(ItemInterface $item, Response $response): ItemInterface
    {
        if ($this->handler instanceof ItemHandlerInterface) {
            return $this->handler->handleItem($item, $response);
        }

        return $item;
    }

    public function handleRequest(Request $request, Response $response): Request
    {
        if ($this->handler instanceof RequestHandlerInterface) {
            return $this->handler->handleRequest($request, $response);
        }

        return $request;
    }

    public function handleResponse(Response $response): Response
    {
        if ($this->handler instanceof ResponseHandlerInterface) {
            return $this->handler->handleResponse($response);
        }

        return $response;
    }

    public function configure(array $options): void
    {
        $this->handler->configure($options);
    }
}
