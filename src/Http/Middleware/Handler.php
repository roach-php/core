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
use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Http\Request;

final class Handler implements HandlerInterface
{
    public function __construct(private Closure $callback)
    {
    }

    public function __invoke(Request $request): PromiseInterface
    {
        return ($this->callback)($request);
    }
}
