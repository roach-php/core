<?php

declare(strict_types=1);

namespace RoachPHP\Tests\Spider\Middleware;

use PHPUnit\Framework\TestCase;
use RoachPHP\Spider\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

/**
 * @internal
 */
final class RequestDeduplicationMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testDropDuplicatedRequest(): void
    {
        $uri = 'http://localhost/';
        $middleware = $this
            ->createMiddleware();

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri), $this->makeResponse());
        self::assertSame(false, $processedRequest->wasDropped());

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri), $this->makeResponse());
        self::assertSame(true, $processedRequest->wasDropped());
    }

    public function testCacheEviction(): void
    {
        $uri_a = 'http://localhost/a';
        $uri_b = 'http://localhost/b';
        $uri_c = 'http://localhost/c';
        $middleware = $this
            ->createMiddleware(2);

        foreach(range(1, 3) as $index) {
            $processedRequest = $middleware
                ->handleRequest($this->makeRequest($uri_a), $this->makeResponse());
        }
        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_b), $this->makeResponse());

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_a), $this->makeResponse());
        self::assertSame(true, $processedRequest->wasDropped());

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_b), $this->makeResponse());
        self::assertSame(true, $processedRequest->wasDropped());

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_c), $this->makeResponse());
        self::assertSame(false, $processedRequest->wasDropped()); // It needs the list of accessed URIs delete some entries.

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_b), $this->makeResponse());
        self::assertSame(false, $processedRequest->wasDropped()); // B was removed from list of duplicated requests.

        $processedRequest = $middleware
            ->handleRequest($this->makeRequest($uri_a), $this->makeResponse());
        self::assertSame(true, $processedRequest->wasDropped()); // A was not removed as it was requested more times than the others.
    }

    private function createMiddleware(?int $cacheSize = null): RequestDeduplicationMiddleware
    {
        $middleware = new RequestDeduplicationMiddleware(new FakeLogger());

        if (null !== $cacheSize) {
            $middleware->configure(['seen_uris_cache_size' => $cacheSize]);
        }

        return $middleware;
    }
}
