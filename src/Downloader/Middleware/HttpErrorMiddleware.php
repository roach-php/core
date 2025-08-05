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

use Psr\Log\LoggerInterface;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

final class HttpErrorMiddleware implements ResponseMiddlewareInterface
{
    use Configurable;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function handleResponse(Response $response): Response
    {
        $status = $response->getStatus();

        if (200 <= $status && 300 > $status) {
            return $response;
        }

        /** @var array<int, int> $allowedStatus */
        $allowedStatus = $this->option('handleStatus');

        if (\in_array($status, $allowedStatus, true)) {
            return $response;
        }

        $this->logger->info(
            '[HttpErrorMiddleware] Dropping unsuccessful response',
            [
                'uri' => $response->getRequest()->getUri(),
                'status' => $status,
            ],
        );

        return $response->drop('Unallowed HTTP status: ' . $status);
    }

    private function defaultOptions(): array
    {
        return [
            'handleStatus' => [],
        ];
    }
}
