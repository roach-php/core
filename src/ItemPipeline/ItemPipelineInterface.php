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

namespace RoachPHP\ItemPipeline;

use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;

interface ItemPipelineInterface
{
    public function setProcessors(ItemProcessorInterface ...$processors): self;

    public function sendItem(ItemInterface $item): ItemInterface;
}
