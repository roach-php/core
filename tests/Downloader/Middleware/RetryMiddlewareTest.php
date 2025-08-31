<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Downloader\Middleware;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\RetryMiddleware;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\Timing\ClockInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

final class RetryMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private RetryMiddleware $middleware;

    private ArrayRequestScheduler $scheduler;

    private FakeLogger $logger;

    protected function setUp(): void
    {
        $this->scheduler = new ArrayRequestScheduler($this->createMock(ClockInterface::class));
        $this->logger = new FakeLogger();
        $this->middleware = new RetryMiddleware($this->scheduler, $this->logger);
    }

    public function testDoesNotRetrySuccessfulResponse(): void
    {
        $response = $this->makeResponse(status: 200);
        $this->middleware->configure([]);

        $result = $this->middleware->handleResponse($response);

        self::assertSame($response, $result);
        self::assertFalse($result->wasDropped());
        self::assertCount(0, $this->scheduler->forceNextRequests(10));
    }

    public function testDoesNotRetryNonRetryableErrorResponse(): void
    {
        $response = $this->makeResponse(status: 404);
        $this->middleware->configure(['retryOnStatus' => [500]]);

        $result = $this->middleware->handleResponse($response);

        self::assertSame($response, $result);
        self::assertFalse($result->wasDropped());
        self::assertCount(0, $this->scheduler->forceNextRequests(10));
    }

    public function testRetriesARetryableResponse(): void
    {
        $request = $this->makeRequest('https://example.com');
        $response = $this->makeResponse(request: $request, status: 503);
        $this->middleware->configure([
            'retryOnStatus' => [503],
            'maxRetries' => 2,
            'backoff' => [1, 2, 3],
        ]);

        $result = $this->middleware->handleResponse($response);

        self::assertTrue($result->wasDropped());

        $retriedRequests = $this->scheduler->forceNextRequests(10);
        self::assertCount(1, $retriedRequests);

        $retriedRequest = $retriedRequests[0];
        self::assertSame(1, $retriedRequest->getMeta('retry_count'));
        self::assertSame('https://example.com', $retriedRequest->getUri());
        self::assertSame(1000, $retriedRequest->getOptions()['delay']);
    }

    public function testStopsRetryingAfterMaxRetries(): void
    {
        $request = $this->makeRequest()->withMeta('retry_count', 3);
        $response = $this->makeResponse(request: $request, status: 500);
        $this->middleware->configure(['maxRetries' => 3, 'backoff' => [1, 2, 3]]);

        $result = $this->middleware->handleResponse($response);

        self::assertSame($response, $result);
        self::assertFalse($result->wasDropped());
        self::assertCount(0, $this->scheduler->forceNextRequests(10));
    }

    public function testUsesBackoffArrayForDelay(): void
    {
        $request = $this->makeRequest()->withMeta('retry_count', 2);
        $response = $this->makeResponse(request: $request, status: 500);
        $this->middleware->configure(['backoff' => [1, 5, 10]]);

        $this->middleware->handleResponse($response);

        $retriedRequest = $this->scheduler->forceNextRequests(10)[0];
        self::assertSame(10000, $retriedRequest->getOptions()['delay']);
    }

    public function testUsesLastBackoffValueIfRetriesExceedBackoffCount(): void
    {
        $request = $this->makeRequest()->withMeta('retry_count', 5);
        $response = $this->makeResponse(request: $request, status: 500);
        $this->middleware->configure(['backoff' => [1, 5, 10], 'maxRetries' => 6]);

        $this->middleware->handleResponse($response);

        $retriedRequest = $this->scheduler->forceNextRequests(10)[0];
        self::assertSame(10000, $retriedRequest->getOptions()['delay']);
    }

    public function testUsesIntegerBackoffForDelay(): void
    {
        $request = $this->makeRequest()->withMeta('retry_count', 2);
        $response = $this->makeResponse(request: $request, status: 500);
        $this->middleware->configure(['backoff' => 5]);

        $this->middleware->handleResponse($response);

        $retriedRequest = $this->scheduler->forceNextRequests(10)[0];
        self::assertSame(5000, $retriedRequest->getOptions()['delay']);
    }

    public static function invalidBackoffProvider(): array
    {
        return [
            'empty array' => [[], 'backoff array cannot be empty.'],
            'array with non-int' => [[1, 'a', 3], 'backoff array must contain only integers. Found: string'],
            'string' => ['not-an-array', 'backoff must be an integer or array, string given.'],
            'float' => [1.23, 'backoff must be an integer or array, double given.'],
        ];
    }

    #[DataProvider('invalidBackoffProvider')]
    public function testThrowsExceptionOnInvalidBackoff(mixed $backoff, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $response = $this->makeResponse(status: 500);
        $this->middleware->configure(['backoff' => $backoff]);

        $this->middleware->handleResponse($response);
    }
}
