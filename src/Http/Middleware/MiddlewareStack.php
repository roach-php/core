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

namespace Sassnowski\Roach\Http\Middleware;

use Closure;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Http\Request;
use Throwable;

final class MiddlewareStack
{
    private array $handlers;

    public function __construct(RequestMiddlewareInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public static function create(RequestMiddlewareInterface ...$middleware): self
    {
        return new self(...$middleware);
    }

    public function dispatchRequest(Request $request, callable $finally): ?PromiseInterface
    {
        $handler = $this->resolve($finally);

        try {
            return $handler($request);
        } catch (DropRequestException) {
            return null;
        } catch (Throwable $e) {
            return $this->rejectPromise($e);
        }
    }

    private function resolve(callable $callback): callable
    {
        return \array_reduce(
            \array_reverse($this->handlers),
            static function (Handler $carry, $handler) {
                return new Handler(static function (Request $value) use ($handler, $carry) {
                    return $handler->handle($value, $carry);
                });
            },
            new Handler(Closure::fromCallable($callback)),
        );
    }

    private function rejectPromise(Throwable $e): PromiseInterface
    {
        $promise = new Promise(static function () use (&$promise, $e): void {
            $promise->reject($e);
        });

        return $promise;
    }
}
