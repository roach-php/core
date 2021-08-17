<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Http\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Http\Request;

final class SetUserAgentMiddleware extends RequestMiddleware
{
    public function __construct(private string $userAgent = 'roach-php')
    {
    }

    public function handle(Request $request, Handler $next): PromiseInterface
    {
        return $next(
            $request->withAddedHeader('User-Agent', $this->userAgent),
        );
    }
}
