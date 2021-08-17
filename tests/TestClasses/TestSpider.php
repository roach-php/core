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
use Generator;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Spider\AbstractSpider;

final class TestSpider extends AbstractSpider
{
    public function __construct(
        protected array $startUrls,
        protected array $middleware,
        protected array $processors,
        private Closure $parseCallback,
    ) {
    }

    public function parse(Response $response): Generator
    {
        return ($this->parseCallback)($response, $this);
    }
}
