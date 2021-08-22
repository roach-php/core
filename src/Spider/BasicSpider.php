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

namespace Sassnowski\Roach\Spider;

use Sassnowski\Roach\Downloader\Middleware\LoggerMiddleware;
use Sassnowski\Roach\Spider\Configuration\ArrayLoader;

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
        LoggerMiddleware::class,
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
