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

namespace Sassnowski\Roach\Spider\Configuration;

use Sassnowski\Roach\Downloader\DownloaderMiddlewareInterface;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;
use Sassnowski\Roach\ResponseProcessing\MiddlewareInterface;

final class Configuration
{
    /**
     * @param string[] $startUrls
     * @psalm-param Array<class-string<DownloaderMiddlewareInterface>> $downloaderMiddleware
     * @psalm-param Array<class-string<ItemProcessorInterface[]>> $itemProcessors
     * @psalm-param Array<class-string<MiddlewareInterface[]>> $spiderMiddleware
     */
    public function __construct(
        public array $startUrls,
        public array $downloaderMiddleware,
        public array $itemProcessors,
        public array $spiderMiddleware,
        public int $concurrency,
        public int $requestDelay,
    ) {
    }
}
