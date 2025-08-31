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

namespace RoachPHP\Downloader\Middleware;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RoachPHP\Http\Response;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Support\Configurable;

final class RetryMiddleware implements ResponseMiddlewareInterface
{
    use Configurable;

    public function __construct(
        private readonly RequestSchedulerInterface $scheduler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleResponse(Response $response): Response
    {
        $request = $response->getRequest();

        /** @var int $retryCount */
        $retryCount = $request->getMeta('retry_count', 0);

        /** @var list<int> $retryOnStatus */
        $retryOnStatus = $this->option('retryOnStatus');

        /** @var int $maxRetries */
        $maxRetries = $this->option('maxRetries');

        if (!\in_array($response->getStatus(), $retryOnStatus, true) || $retryCount >= $maxRetries) {
            return $response;
        }

        $delay = $this->getDelay($retryCount);

        $this->logger->info(
            'Retrying request',
            [
                'uri' => $request->getUri(),
                'status' => $response->getStatus(),
                'retry_count' => $retryCount + 1,
                'delay_ms' => $delay,
            ],
        );

        $retryRequest = $request
            ->withMeta('retry_count', $retryCount + 1)
            ->addOption('delay', $delay);

        $this->scheduler->schedule($retryRequest);

        return $response->drop('Request being retried');
    }

    private function getDelay(int $retryCount): int
    {
        /** @var int|list<int> $backoff */
        $backoff = $this->option('backoff');

        if (\is_int($backoff)) {
            return $backoff * 1000;
        }

        if (!\is_array($backoff)) {
            throw new InvalidArgumentException(
                'backoff must be an integer or array, ' . \gettype($backoff) . ' given.',
            );
        }

        if ([] === $backoff) {
            throw new InvalidArgumentException('backoff array cannot be empty.');
        }

        $nonIntegerValues = \array_filter($backoff, static fn ($value) => !\is_int($value));

        if ([] !== $nonIntegerValues) {
            throw new InvalidArgumentException(
                'backoff array must contain only integers. Found: ' .
                \implode(', ', \array_map('gettype', $nonIntegerValues)),
            );
        }

        $delay = $backoff[$retryCount] ?? $backoff[\array_key_last($backoff)];

        return $delay * 1000;
    }

    private static function defaultOptions(): array
    {
        return [
            'retryOnStatus' => [500, 502, 503, 504],
            'maxRetries' => 3,
            'backoff' => [1, 5, 10],
        ];
    }
}
