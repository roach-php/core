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

use Psr\Log\LoggerInterface;
use Sassnowski\Roach\ItemPipeline\Processors\DropItemCallback;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;

final class ImmutableItemPipeline implements ItemPipelineInterface
{
    /**
     * @var ItemProcessorInterface[]
     */
    private array $processors = [];

    public function __construct(private LoggerInterface $logger)
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
        $dropped = false;

        $dropItem = new DropItemCallback(static function (ItemInterface $item) use (&$dropped) {
            $dropped = true;

            return $item;
        });

        foreach ($this->processors as $processor) {
            $item = $processor->processItem($item, $dropItem);

            if ($dropped) {
                $this->logger?->info('[Item pipeline] Item was dropped', ['item' => $item->all()]);

                break;
            }
        }

        return $item;
    }
}
