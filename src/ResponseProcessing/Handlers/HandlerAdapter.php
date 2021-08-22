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

namespace Sassnowski\Roach\ResponseProcessing\Handlers;

use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ResponseProcessing\ItemHandlerInterface;
use Sassnowski\Roach\ResponseProcessing\MiddlewareInterface;
use Sassnowski\Roach\ResponseProcessing\RequestHandlerInterface;
use Sassnowski\Roach\ResponseProcessing\ResponseHandlerInterface;

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
