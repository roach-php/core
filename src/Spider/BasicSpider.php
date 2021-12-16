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

namespace RoachPHP\Spider;

use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\Extension;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\Configuration\ArrayLoader;

abstract class BasicSpider extends AbstractSpider
{
    /**
     * @var string[]
     */
    public array $startUrls = [];

    /**
     * @var class-string[]
     */
    public array $spiderMiddleware = [];

    /**
     * @var class-string[]
     */
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    /**
     */
    public array $itemProcessors = [];

    /**
     * @var class-string[]
     */
    public array $extensions = [];

    public int $concurrency = 5;

    public int $requestDelay = 1;

    public function __construct()
    {
        parent::__construct(new ArrayLoader([
            'startUrls' => $this->startUrls,
            'downloaderMiddleware' => $this->downloaderMiddleware,
            'spiderMiddleware' => $this->spiderMiddleware,
            'itemProcessors' => $this->itemProcessors,
            'extensions' => $this->extensions,
            'concurrency' => $this->concurrency,
            'requestDelay' => $this->requestDelay,
        ]));
    }
}
