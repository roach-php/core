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
use const HTTP_URL_REPLACE;
use const HTTP_URL_STRIP_FRAGMENT;
use const HTTP_URL_STRIP_QUERY;

final class RequestDeduplicationMiddleware extends RequestMiddleware
{
    /**
     * @var string[]
     */
    private array $seenUris = [];

    public function __construct(private LoggerInterface $logger)
    {
        parent::__construct([
            'ignore_url_fragments' => false,
            'ignore_trailing_slashes' => true,
            'ignore_query_string' => false,
        ]);
    }

    public function handle(Request $request, HandlerInterface $next): PromiseInterface
    {
        $uri = $request->getUri();
        $replaceFlags = HTTP_URL_REPLACE;
        $parts = \parse_url($uri);

        if ($this->options['ignore_url_fragments']) {
            $replaceFlags |= HTTP_URL_STRIP_FRAGMENT;
        }

        /** @phpstan-ignore-next-line */
        if ($this->options['ignore_trailing_slashes'] && isset($parts['path'])) {
            $parts['path'] = \rtrim($parts['path'], '/');
        }

        if ($this->options['ignore_query_string']) {
            $replaceFlags |= HTTP_URL_STRIP_QUERY;
        }

        /** @phpstan-ignore-next-line */
        $uri = http_build_url($uri, $parts, $replaceFlags);

        if (\in_array($uri, $this->seenUris, true)) {
            $this->logger->info(
                '[RequestDeduplicationMiddleware] Dropping duplicate request',
                ['uri' => $request->getUri()],
            );

            $this->dropRequest($request);
        }

        $this->seenUris[] = $uri;

        return $next($request);
    }
}
