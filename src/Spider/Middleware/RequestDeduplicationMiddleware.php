<?php

namespace RoachPHP\Spider\Middleware;

use Psr\Log\LoggerInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

/**
 * Drop duplicate requests to avoid unnecessary work.
 *
 * This middleware is optimized for reducing memory usage as it drops duplicate
 * requests early in the request processing pipeline, and it uses a limited-size
 * cache to store seen URIs.
 */
class RequestDeduplicationMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    private array $seenUriHashesHits = [];
    private int $requestCount = 0;
    private int $requestDroppedCount = 0;

    public function __construct(private LoggerInterface $logger)
    {
    }

    private function defaultOptions(): array
    {
        return [
            'ignore_url_fragments' => false,
            'ignore_trailing_slashes' => true,
            'ignore_query_string' => false,
            'seen_uris_cache_size' => 10000,
        ];
    }

    public function handleRequest(Request $request, Response $response): Request
    {
        $uri = $request->getUri();

        if ($this->isDuplicatedUri($uri)) {
            $this->logger->info(
                '[RequestDeduplicationMiddleware] Dropping duplicate request',
                ['uri' => $uri],
            );

            return $request->drop('Duplicate request');
        }

        return $request;
    }

    private function isDuplicatedUri(string $uri): bool
    {
        $uriHash = $this->hashUri($uri);

        if (isset($this->seenUriHashesHits[$uriHash])) {
            $this->seenUriHashesHits[$uriHash] += 1;
            return true;
        }

        $this->seenUriHashesHits[$uriHash] = 1;
        $this->cacheEviction();
        return false;
    }

    private function hashUri(string $uri): string
    {
        $replaceFlags = HTTP_URL_REPLACE;
        $parts = parse_url($uri);

        if ($this->option('ignore_url_fragments')) {
            $replaceFlags |= HTTP_URL_STRIP_FRAGMENT;
        }

        if ($this->option('ignore_trailing_slashes') && isset($parts['path'])) {
            $parts['path'] = rtrim($parts['path'], '/');
        }

        if ($this->option('ignore_query_string')) {
            $replaceFlags |= HTTP_URL_STRIP_QUERY;
        }

        $uri = http_build_url($uri, $parts, $replaceFlags);
        return md5($uri); // Why md5? Because it's fast and short.
    }

    private function cacheEviction(): void
    {
        if (count($this->seenUriHashesHits) <= $this->option('seen_uris_cache_size')) {
            return;
        }

        $averageHitCount = array_sum($this->seenUriHashesHits) / count($this->seenUriHashesHits);
        $this->seenUriHashesHits = array_filter($this->seenUriHashesHits, fn($hitCount) => $hitCount > $averageHitCount);

        $this->logger->info(
            '[RequestDeduplicationMiddleware] Cache eviction',
            [
                'average_hit_count' => $averageHitCount,
                'remaining_cache_size' => count($this->seenUriHashesHits),
            ],
        );
    }
}
