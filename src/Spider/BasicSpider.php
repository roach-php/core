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
use RoachPHP\Spider\Configuration\ArrayLoader;

abstract class BasicSpider extends AbstractSpider
{
    /**
     * @var string[]
     */
    public array $startUrls = [];

    /**
     * @var string[]
     */
    public array $spiderMiddleware = [];

    /**
     * @var string[]
     */
    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    /**
     * @var string[]
     */
    public array $itemProcessors = [];

    public int $concurrency = 5;

    public int $requestDelay = 1;

    public function __construct()
    {
        parent::__construct(new ArrayLoader([
            'startUrls' => $this->startUrls,
            'downloaderMiddleware' => $this->downloaderMiddleware,
            'spiderMiddleware' => $this->spiderMiddleware,
            'itemProcessors' => $this->itemProcessors,
            'concurrency' => $this->concurrency,
            'requestDelay' => $this->requestDelay,
        ]));
    }
}
