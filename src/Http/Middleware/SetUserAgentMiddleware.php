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

use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Http\Request;

final class SetUserAgentMiddleware implements RequestMiddlewareInterface
{
    public function __construct(private string $userAgent = 'roach-php')
    {
    }

    public function handle(Request $request, HandlerInterface $next): PromiseInterface
    {
        return $next($request->withHeader('User-Agent', $this->userAgent));
    }
}
