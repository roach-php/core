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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sassnowski\Roach\Http\Middleware\LogMiddleware;
use Sassnowski\Roach\Http\Middleware\RequestDeduplicationMiddleware;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

abstract class AbstractSpider
{
    public static string $name = 'spider_name';

    protected array $startUrls = [];

    private array $itemPipeline;

    public function __construct(private ?array $middleware = null, array $itemPipeline = [])
    {
        $this->itemPipeline = $itemPipeline;
    }

    abstract public function parse(Response $response): Generator;

    final public function middleware(): array
    {
        return $this->getMiddleware();
    }

    final public function getItemProcessors(): array
    {
        return $this->itemPipeline;
    }

    /**
     * @return Generator|Request[]
     */
    final public function startRequests(): Generator
    {
        foreach ($this->getStartUrls() as $url) {
            yield new Request($url, [$this, 'parse']);
        }
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
        return $this->middleware ?? $this->defaultMiddleware();
    }

    protected function defaultMiddleware(): array
    {
        $logger = new Logger(static::$name);
        $logger->pushHandler(new StreamHandler('php://stdout'));

        return [
            new RequestDeduplicationMiddleware($logger),
            new LogMiddleware($logger),
        ];
    }
}
