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

namespace Sassnowski\Roach\Core;

use Sassnowski\Roach\Http\Middleware\MiddlewareStack as HttpMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Parsing\MiddlewareStack as ResponseMiddleware;

final class Run
{
    public function __construct(
        private array $startRequests,
        private HttpMiddleware $middlewareStack,
        private ItemPipelineInterface $itemPipeline,
        private ResponseMiddleware $responseMiddleware,
        private int $concurrency = 25,
        private int $delay = 0,
    ) {
    }

    /**
     * @return Request[]
     */
    public function startRequests(): array
    {
        return $this->startRequests;
    }

    public function httpMiddleware(): HttpMiddleware
    {
        return $this->middlewareStack;
    }

    public function itemPipeline(): ItemPipelineInterface
    {
        return $this->itemPipeline;
    }

    public function responseMiddleware(): ResponseMiddleware
    {
        return $this->responseMiddleware;
    }

    public function concurrency(): int
    {
        return $this->concurrency;
    }

    public function requestDelay(): int
    {
        return $this->delay;
    }
}
