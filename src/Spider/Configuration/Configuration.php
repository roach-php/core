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

namespace RoachPHP\Spider\Configuration;

use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Extensions\Extension;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\ResponseProcessing\MiddlewareInterface;

final class Configuration
{
    /**
     * @param string[] $startUrls
     * @psalm-param class-string<DownloaderMiddlewareInterface>[] $downloaderMiddleware
     * @psalm-param class-string<ItemProcessorInterface>[] $itemProcessors
     * @psalm-param class-string<MiddlewareInterface>[] $spiderMiddleware
     * @psalm-param class-string<Extension>[] $extensions
     */
    public function __construct(
        public array $startUrls,
        public array $downloaderMiddleware,
        public array $itemProcessors,
        public array $spiderMiddleware,
        public array $extensions,
        public int $concurrency,
        public int $requestDelay,
    ) {
    }
}
