<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Spider;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractSpider
{
    public static string $name = 'spider_name';

    public ?string $baseUri = null;

    protected array $startUrls = [];

    abstract public function parse(Crawler $response): Generator;

    /**
     * @return Generator|Request[]
     */
    final public function startRequests(): Generator
    {
        foreach ($this->getStartUrls() as $url) {
            yield new Request($url, 'GET');
        }
    }

    protected function getStartUrls(): array
    {
        return $this->startUrls;
    }
}
