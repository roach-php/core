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

use Sassnowski\Roach\Events\ItemDropped;
use Sassnowski\Roach\Events\ItemScraped;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ImmutableItemPipeline implements ItemPipelineInterface
{
    /**
     * @var ItemProcessorInterface[]
     */
    private array $processors = [];

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function setProcessors(ItemProcessorInterface ...$processors): ItemPipelineInterface
    {
        $pipeline = clone $this;
        $pipeline->processors = $processors;

        return $pipeline;
    }

    public function sendItem(ItemInterface $item): ItemInterface
    {
        foreach ($this->processors as $processor) {
            $item = $processor->processItem($item);

            if ($item->wasDropped()) {
                $this->eventDispatcher->dispatch(
                    new ItemDropped($item),
                    ItemDropped::NAME,
                );

                return $item;
            }
        }

        $this->eventDispatcher->dispatch(
            new ItemScraped($item),
            ItemScraped::NAME,
        );

        return $item;
    }
}
