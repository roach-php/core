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

use Generator;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ResponseProcessing\ParseResult;
use RoachPHP\Spider\Configuration\Configuration;

abstract class AbstractSpider implements SpiderInterface
{
    protected Configuration $configuration;

    public function __construct(ConfigurationLoaderStrategy $loaderStrategy)
    {
        $this->configuration = $loaderStrategy->load();
    }

    /**
     * @psalm-return Generator<ParseResult>
     */
    abstract public function parse(Response $response): Generator;

    /**
     * @return Request[]
     */
    final public function getInitialRequests(): array
    {
        return \array_map(function (string $url) {
            return new Request($url, [$this, 'parse']);
        }, $this->getStartUrls());
    }

    final public function loadConfiguration(): Configuration
    {
        return $this->configuration;
    }

    protected function request(string $url, string $parseMethod = 'parse'): ParseResult
    {
        return ParseResult::request($url, [$this, $parseMethod]);
    }

    protected function item(array $item): ParseResult
    {
        return ParseResult::item($item);
    }

    /**
     * @return string[]
     */
    protected function getStartUrls(): array
    {
        return $this->configuration->startUrls;
    }
}
