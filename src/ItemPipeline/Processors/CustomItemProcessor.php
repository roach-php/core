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

namespace RoachPHP\ItemPipeline\Processors;

use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Support\Configurable;

abstract class CustomItemProcessor implements ConditionalItemProcessor
{
    use Configurable;

    final public function shouldHandle(ItemInterface $item): bool
    {
        return \in_array($item::class, $this->getHandledItemClasses(), true);
    }

    /**
     * @return array<int, class-string<ItemInterface>>
     */
    abstract protected function getHandledItemClasses(): array;
}
