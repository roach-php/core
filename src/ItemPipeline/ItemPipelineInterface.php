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

namespace Sassnowski\Roach\ItemPipeline;

use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;

interface ItemPipelineInterface
{
    public function setProcessors(ItemProcessorInterface ...$processor): self;

    public function sendItem(ItemInterface $item): ItemInterface;
}
