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

use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\Testing\FakeLogger;
use Sassnowski\Roach\Tests\InteractsWithPipelines;

/**
 * @group items
 *
 * @internal
 */
final class ImmutableItemPipelineTest extends TestCase
{
    use InteractsWithPipelines;

    private ImmutableItemPipeline $pipeline;

    private FakeLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger();
        $this->pipeline = new ImmutableItemPipeline($this->logger);
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

    public function testLogIfItemGotDropped(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $item = new Item(['foo' => 'bar']);

        $this->pipeline
            ->setProcessors($processor)
            ->sendItem($item);

        self::assertTrue(
            $this->logger->messageWasLogged('info', '[Item pipeline] Item was dropped', [
                'item' => $item->all(),
                'reason' => '::reason::',
            ]),
        );
    }
}
