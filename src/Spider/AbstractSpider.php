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

use Generator;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

abstract class AbstractSpider
{
    public static string $name = 'spider_name';

    protected array $startUrls = [];

    protected array $middleware = [];

    protected array $processors = [];

    abstract public function parse(Response $response): Generator;

    final public function middleware(): array
    {
        return $this->getMiddleware();
    }

    final public function processors(): array
    {
        return $this->getProcessors();
    }

    /**
     * @return Request[]
     */
    final public function startRequests(): array
    {
        return \array_map(
            fn (string $url) => new Request($url, [$this, 'parse']),
            $this->getStartUrls(),
        );
    }

    protected function getStartUrls(): array
    {
        return $this->startUrls;
    }

    protected function request(string $url, string $parseMethod = 'parse'): ParseResult
    {
        return ParseResult::request($url, [$this, $parseMethod]);
    }

    protected function item(mixed $item): ParseResult
    {
        return ParseResult::item($item);
    }

    protected function getMiddleware(): array
    {
        return $this->middleware;
    }

    protected function getProcessors(): array
    {
        return $this->processors;
    }
}
