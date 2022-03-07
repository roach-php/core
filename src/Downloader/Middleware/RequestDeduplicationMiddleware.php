<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader\Middleware;

use Psr\Log\LoggerInterface;
use RoachPHP\Http\Request;
use RoachPHP\Support\Configurable;
use const HTTP_URL_REPLACE;
use const HTTP_URL_STRIP_FRAGMENT;
use const HTTP_URL_STRIP_QUERY;

final class RequestDeduplicationMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    /**
     * @var string[]
     */
    private array $seenUris = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handleRequest(Request $request): Request
    {
        $uri = $request->getUri();
        $replaceFlags = HTTP_URL_REPLACE;
        $parts = \parse_url($uri);

        if ($this->option('ignore_url_fragments')) {
            $replaceFlags |= HTTP_URL_STRIP_FRAGMENT;
        }

        if ($this->option('ignore_trailing_slashes') && isset($parts['path'])) {
            $parts['path'] = \rtrim($parts['path'], '/');
        }

        if ($this->option('ignore_query_string')) {
            $replaceFlags |= HTTP_URL_STRIP_QUERY;
        }

        $uri = http_build_url($uri, $parts, $replaceFlags);

        if (\in_array($uri, $this->seenUris, true)) {
            $this->logger->info(
                '[RequestDeduplicationMiddleware] Dropping duplicate request',
                ['uri' => $request->getUri()],
            );

            return $request->drop('Duplicate request');
        }

        $this->seenUris[] = $uri;

        return $request;
    }

    private function defaultOptions(): array
    {
        return [
            'ignore_url_fragments' => false,
            'ignore_trailing_slashes' => true,
            'ignore_query_string' => false,
        ];
    }
}
