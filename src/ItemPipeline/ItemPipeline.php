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

namespace RoachPHP\ItemPipeline;

use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ItemPipeline implements ItemPipelineInterface
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
        $this->processors = $processors;

        return $this;
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
