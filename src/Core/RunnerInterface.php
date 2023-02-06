<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Core;

use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Spider\SpiderInterface;

interface RunnerInterface
{
    /**
     * @param class-string<SpiderInterface> $spiderClass
     */
    public function startSpider(
        string $spiderClass,
        ?Overrides $overrides = null,
        array $context = [],
    ): void;

    /**
     * @param class-string<SpiderInterface> $spiderClass
     *
     * @return array<int, ItemInterface>
     */
    public function collectSpider(
        string $spiderClass,
        ?Overrides $overrides = null,
        array $context = [],
    ): array;
}
