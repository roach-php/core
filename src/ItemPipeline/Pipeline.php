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

final class Pipeline
{
    public function __construct(private array $processors)
    {
    }

    public function sendThroughPipeline(mixed $item): void
    {
        foreach ($this->processors as $processor) {
            $item = $processor->processItem($item);
        }
    }
}
