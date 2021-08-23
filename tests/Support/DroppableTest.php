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

namespace RoachPHP\Tests\Support;

use PHPUnit\Framework\TestCase;
use RoachPHP\Support\DroppableInterface;

/**
 * @mixin TestCase
 */
trait DroppableTest
{
    public function testWasDropped(): void
    {
        $droppable = $this->createDroppable();

        self::assertFalse($droppable->wasDropped());

        $droppable = $droppable->drop('::reason::');

        self::assertTrue($droppable->wasDropped());
    }

    public function testGetReason(): void
    {
        $droppable = $this->createDroppable();

        $dropped = $droppable->drop('::reason::');

        self::assertSame('::reason::', $dropped->getDropReason());
    }

    abstract protected function createDroppable(): DroppableInterface;
}
