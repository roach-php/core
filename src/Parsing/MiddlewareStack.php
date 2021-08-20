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

namespace Sassnowski\Roach\Parsing;

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

    public static function create(MiddlewareInterface ...$handlers)
    {
        return new self($handlers);
    }

    public function handle(Response $response): Generator
    {
        foreach ($this->handlers as $handler) {
            $response = $handler->handleResponse($response);
        }

        /** @var ParseResult[] $results */
        $results = $response->getRequest()->callback($response);

        foreach ($results as $result) {
            $value = $result->value();

            if ($value instanceof Request) {
                $dropRequest = new DropRequest($value);

                foreach ($this->handlers as $handler) {
                    $value = $handler->handleRequest($value, $response, $dropRequest);

                    if ($dropRequest->dropped()) {
                        break;
                    }
                }

                if (!$dropRequest->dropped()) {
                    yield ParseResult::fromValue($value);
                }
            } else {
                foreach ($this->handlers as $handler) {
                    $value = $handler->handleItem($value, $response);
                }

                yield ParseResult::fromValue($value);
            }
        }
    }
}
