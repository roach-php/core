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

namespace RoachPHP\Core;

use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Http\Request;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\SpiderMiddlewareInterface;

/**
 * @psalm-immutable
 */
final class Run
{
    /**
     * @param array<Request>                       $startRequests
     * @param array<DownloaderMiddlewareInterface> $downloaderMiddleware
     * @param array<ItemProcessorInterface>        $itemProcessors
     * @param array<SpiderMiddlewareInterface>     $responseMiddleware
     * @param array<ExtensionInterface>            $extensions
     */
    public function __construct(
        public array $startRequests,
        public string $namespace,
        public array $downloaderMiddleware = [],
        public array $itemProcessors = [],
        public array $responseMiddleware = [],
        public array $extensions = [],
        public int $concurrency = 25,
        public int $requestDelay = 0,
    ) {
    }
}
