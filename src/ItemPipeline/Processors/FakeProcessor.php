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

namespace RoachPHP\ItemPipeline\Processors;

use PHPUnit\Framework\Assert;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\ItemProcessor;

final class FakeProcessor extends ItemProcessor
{
    private array $calls = [];

    public function processItem(ItemInterface $item): ItemInterface
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
