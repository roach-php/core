<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\ItemPipeline;

use PHPUnit\Framework\TestCase;
use RoachPHP\Tests\Fixtures\TestCustomItemProcessor;
use RoachPHP\Tests\Fixtures\TestItem;
use RoachPHP\Tests\Fixtures\TestItem2;

/**
 * @internal
 */
final class CustomItemProcessorTest extends TestCase
{
    public function testHandlesItemsDefinedByTheChildClass(): void
    {
        $processor = new TestCustomItemProcessor([TestItem::class]);
        self::assertTrue(
            $processor->shouldHandle(new TestItem('::foo::', '::bar::')),
        );

        $processor = new TestCustomItemProcessor([TestItem2::class]);
        self::assertTrue(
            $processor->shouldHandle(new TestItem2()),
        );
    }

    public function testDoesNotHandleItemsNotDefinedInTheChildClass(): void
    {
        $processor = new TestCustomItemProcessor([TestItem::class]);
        self::assertFalse(
            $processor->shouldHandle(new TestItem2()),
        );

        $processor = new TestCustomItemProcessor([TestItem2::class]);
        self::assertFalse(
            $processor->shouldHandle(new TestItem('::foo::', '::bar::')),
        );
    }
}
