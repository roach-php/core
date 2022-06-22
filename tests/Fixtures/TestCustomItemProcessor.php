<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\CustomItemProcessor;

final class TestCustomItemProcessor extends CustomItemProcessor
{
    /**
     * @param array<int, class-string<ItemInterface>> $handledItemClasses
     */
    public function __construct(private array $handledItemClasses)
    {
    }

    public function processItem(ItemInterface $item): ItemInterface
    {
        return $item->drop('::reason::');
    }

    protected function getHandledItemClasses(): array
    {
        return $this->handledItemClasses;
    }
}
