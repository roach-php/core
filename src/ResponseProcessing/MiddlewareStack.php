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
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

final class MiddlewareStack
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $handlers;

    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public static function create(MiddlewareInterface ...$handlers): self
    {
        return new self($handlers);
    }

    public function handle(Response $response): Generator
    {
        foreach ($this->handlers as $handler) {
            $response = $handler->handleResponse($response);

            if ($response->wasDropped()) {
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

            foreach ($this->handlers as $handler) {
                $value = $handler->{$handleMethod}($value, $response);

                if ($value->wasDropped()) {
                    break;
                }
            }

            if (!$value->wasDropped()) {
                yield ParseResult::fromValue($value);
            }
        }
    }
}
