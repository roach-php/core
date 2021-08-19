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

namespace Sassnowski\Roach\Testing;

use PHPUnit\Framework\Assert;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ItemPipeline\Processors\DropItemCallback;
use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;

final class FakeProcessor implements ItemProcessorInterface
{
    private array $calls = [];

    public function processItem(ItemInterface $item, DropItemCallback $dropItem): ItemInterface
    {
        $this->calls[] = $item->all();

        return $item;
    }

    public function assertCalledWith(ItemInterface $item): void
    {
        Assert::assertContains(
            $item->all(),
            $this->calls,
            'Processor was not called with expected item',
        );
    }

    public function assertNotCalledWith(ItemInterface $item): void
    {
        Assert::assertNotContains(
            $item->all(),
            $this->calls,
            'Processor got unexpected call with item',
        );
    }

    public function assertNotCalled(): void
    {
        Assert::assertEmpty(
            $this->calls,
            \sprintf('Expected processor to not have been called at all. Was called %s time(s)', \count($this->calls)),
        );
    }
}
