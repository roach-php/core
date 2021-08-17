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

namespace Sassnowski\Roach\Tests\TestClasses;

use Closure;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Spider\ParseResult;

final class SpiderBuilder
{
    private array $startUrls;

    private array $middleware = [];

    private array $itemProcessors = [];

    private ?Closure $parseCallback = null;

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public function withMiddleware(string ...$middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function parseResponse(Closure $parse): self
    {
        $this->parseCallback = $parse;

        return $this;
    }

    public function withItemProcessors(...$processors): self
    {
        $this->itemProcessors = $processors;

        return $this;
    }

    public function withStartUrls(string ...$urls): self
    {
        $this->startUrls = $urls;

        return $this;
    }

    public function build(): TestSpider
    {
        return new TestSpider(
            $this->startUrls,
            $this->middleware,
            $this->itemProcessors,
            $this->parseCallback ?: static fn (Response $response) => yield ParseResult::item(''),
        );
    }
}
