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

namespace Sassnowski\Roach\Tests;

use Closure;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ItemPipeline\Processors\DropItemCallback;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;

trait InteractsWithPipelines
{
    protected function makeProcessor(callable $processItem): ItemProcessorInterface
    {
        return new class($processItem) implements ItemProcessorInterface {
            public function __construct(private Closure $processItem)
            {
            }

            public function processItem(ItemInterface $item, DropItemCallback $dropItem): ItemInterface
            {
                return ($this->processItem)($item, $dropItem);
            }
        };
    }
}
