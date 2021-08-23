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

namespace RoachPHP\Downloader\Middleware;

use Psr\Log\LoggerInterface;
use RoachPHP\Http\Request;

final class LoggerMiddleware extends DownloaderMiddleware implements RequestMiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
        parent::__construct();
    }

    public function handleRequest(Request $request): Request
    {
        $this->logger->info('[LoggerMiddleware] Dispatching request', [
            'uri' => $request->getUri(),
        ]);

        return $request;
    }
}
