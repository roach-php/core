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
use Monolog\Logger;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

final class LogMiddleware implements RequestMiddlewareInterface
{
    public function __construct(private Logger $logger)
    {
    }

    public function handle(Request $request, Handler $next): PromiseInterface
    {
        $this->logger->info('Start crawling site', ['uri' => (string) $request->getUri()]);

        return $next($request)->then(static function (Response $response) {
            return $response;
        });
    }
}
