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

final class RequestDeduplicationMiddleware extends RequestMiddleware
{
    /**
     * @var string[]
     */
    private array $seenUris = [];

    public function __construct(private Logger $logger)
    {
    }

    public function handle(Request $request, Handler $next): PromiseInterface
    {
        $uri = (string) $request->getUri();

        if (\in_array($uri, $this->seenUris, true)) {
            $this->logger->info('Dropping duplicate request', ['uri' => $uri]);
            $this->dropRequest($request);
        }

        $this->seenUris[] = $uri;

        return $next($request);
    }
}
