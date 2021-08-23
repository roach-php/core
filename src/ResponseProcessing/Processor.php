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

namespace Sassnowski\Roach\ResponseProcessing;

use Generator;
use Sassnowski\Roach\Events\ItemDropped;
use Sassnowski\Roach\Events\RequestDropped;
use Sassnowski\Roach\Events\ResponseDropped;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Processor
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middleware = [];

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function withMiddleware(MiddlewareInterface ...$middleware): Processor
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function handle(Response $response): Generator
    {
        foreach ($this->middleware as $handler) {
            $response = $handler->handleResponse($response);

            if ($response->wasDropped()) {
                $this->eventDispatcher->dispatch(
                    new ResponseDropped($response),
                    ResponseDropped::NAME
                );

                return;
            }
        }

        /** @var ParseResult[] $results */
        $results = $response->getRequest()->callback($response);

        foreach ($results as $result) {
            $value = $result->value();
            $handleMethod = $value instanceof Request
                ? 'handleRequest'
                : 'handleItem';

            foreach ($this->middleware as $handler) {
                $value = $handler->{$handleMethod}($value, $response);

                if ($value->wasDropped()) {
                    if ($value instanceof Request) {
                        $this->eventDispatcher->dispatch(
                            new RequestDropped($value),
                            RequestDropped::NAME,
                        );
                    } else {
                        $this->eventDispatcher->dispatch(
                            new ItemDropped($value),
                            ItemDropped::NAME,
                        );
                    }

                    break;
                }
            }

            if (!$value->wasDropped()) {
                yield ParseResult::fromValue($value);
            }
        }
    }
}
