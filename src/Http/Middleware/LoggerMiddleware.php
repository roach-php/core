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
use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http\Request;

final class LoggerMiddleware implements RequestMiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handle(Request $request, HandlerInterface $next): PromiseInterface
    {
        $this->logger?->info(
            '[LoggerMiddleware] Dispatching request',
            ['uri' => (string) $request->getUri()],
        );

        return $next($request);
    }
}
