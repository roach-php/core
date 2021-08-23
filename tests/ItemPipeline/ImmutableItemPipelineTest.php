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

namespace Sassnowski\Roach\Tests\ItemPipeline;

use Closure;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Events\FakeDispatcher;
use Sassnowski\Roach\Events\ItemDropped;
use Sassnowski\Roach\Events\ItemScraped;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ItemPipeline\ItemProcessor;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;
use Sassnowski\Roach\Testing\FakeLogger;

/**
 * @group items
 *
 * @internal
 */
final class ImmutableItemPipelineTest extends TestCase
{
    private ImmutableItemPipeline $pipeline;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
        $this->pipeline = new ImmutableItemPipeline($this->dispatcher);
    }

    public function testSettingProcessorsReturnsANewPipelineInstance(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item);

        $pipeline = $this->pipeline->setProcessors($processor);

        self::assertNotSame($this->pipeline, $pipeline);
    }

    public function testCallsProcessorsInOrder(): void
    {
        $processorA = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value') . 'A'),
        );
        $processorB = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value') . 'B'),
        );

        $result = $this->pipeline
            ->setProcessors($processorA, $processorB)
            ->sendItem(new Item(['value' => 'C']));

        self::assertSame('CAB', $result->get('value'));
    }

    public function testDontCallNextProcessorsIfItemWasDropped(): void
    {
        $processorA = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value') . 'A'),
        );
        $processorB = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $processorC = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value') . 'C'),
        );

        $result = $this->pipeline
            ->setProcessors($processorA, $processorB, $processorC)
            ->sendItem(new Item(['value' => '']));

        self::assertSame('A', $result->get('value'));
    }

    public function testDispatchesEventIfItemWasDropped(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $item = new Item(['foo' => 'bar']);

        $this->pipeline
            ->setProcessors($processor)
            ->sendItem($item);

        $this->dispatcher->assertDispatched(
            ItemDropped::NAME,
            fn (ItemDropped $event) => $event->item->all() === $item->all()
        );
    }

    public function testDoesNotDispatchEventIfItemWasNotDropped(): void
    {
        $this->pipeline->sendItem(new Item([]));

        $this->dispatcher->assertNotDispatched(ItemDropped::NAME);
    }

    public function testDispatchesEventIfItemWasScraped(): void
    {
        $this->pipeline->sendItem(new Item(['foo' => 'bar']));

        $this->dispatcher->assertDispatched(
            ItemScraped::NAME,
            fn (ItemScraped $event) => $event->item->all() === ['foo' => 'bar']
        );
    }

    public function testDoesNotDispatchEventIfItemWasNotScraped(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $this->pipeline
            ->setProcessors($processor)
            ->sendItem(new Item([]));

        $this->dispatcher->assertNotDispatched(ItemScraped::NAME);
    }

    private function makeProcessor(Closure $processItem): ItemProcessorInterface
    {
        return new class($processItem) extends ItemProcessor {
            public function __construct(private Closure $processItem)
            {
                parent::__construct();
            }

            public function processItem(ItemInterface $item): ItemInterface
            {
                return ($this->processItem)($item);
            }
        };
    }
}
