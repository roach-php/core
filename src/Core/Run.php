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

namespace RoachPHP\Core;

use RoachPHP\Downloader\Downloader;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Http\Request;
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\Processor;
use RoachPHP\Spider\SpiderMiddlewareInterface;

/**
 * @psalm-immutable
 */
final class Run
{
    /**
     * @param Request[]                       $startRequests
     * @param DownloaderMiddlewareInterface[] $downloaderMiddleware
     * @param ItemProcessorInterface[]        $itemProcessors
     * @param SpiderMiddlewareInterface[]     $responseMiddleware
     * @param ExtensionInterface[]            $extensions
     * @param null|Downloader                 $downloader
     * @param null|ItemPipelineInterface      $itemPipeline
     * @param null|Processor                  $responseProcessor
     */
    public function __construct(
        public array $startRequests,
        public array $downloaderMiddleware = [],
        public array $itemProcessors = [],
        public array $responseMiddleware = [],
        public array $extensions = [],
        public int $concurrency = 25,
        public int $requestDelay = 0,
        public ?Downloader $downloader = null,
        public ?ItemPipelineInterface $itemPipeline = null,
        public ?Processor $responseProcessor = null,
    ) {
    }
}
