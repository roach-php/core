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

namespace RoachPHP\Spider;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Configuration\Configuration;

abstract class AbstractSpider implements SpiderInterface
{
    protected Configuration $configuration;

    protected array $context = [];

    public function __construct(ConfigurationLoaderStrategy $loaderStrategy)
    {
        $this->configuration = $loaderStrategy->load();
    }

    /**
     * @psalm-return \Generator<ParseResult>
     */
    abstract public function parse(Response $response): \Generator;

    /**
     * @return list<Request>
     */
    final public function getInitialRequests(): array
    {
        return $this->initialRequests();
    }

    final public function withConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    final public function withContext(array $context): void
    {
        $this->context = $context;
    }

    final public function loadConfiguration(): Configuration
    {
        return $this->configuration;
    }

    protected function request(
        string $method,
        string $url,
        string $parseMethod = 'parse',
        array $options = [],
    ): ParseResult {
        return ParseResult::request($method, $url, [$this, $parseMethod], $options);
    }

    protected function item(array|ItemInterface $item): ParseResult
    {
        if ($item instanceof ItemInterface) {
            return ParseResult::fromValue($item);
        }

        return ParseResult::item($item);
    }

    /**
     * @return list<Request>
     */
    protected function initialRequests(): array
    {
        return \array_map(function (string $url) {
            return new Request('GET', $url, [$this, 'parse']);
        }, $this->configuration->startUrls);
    }
}
