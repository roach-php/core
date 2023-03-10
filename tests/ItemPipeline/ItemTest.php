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
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Tests\Support\DroppableTestCase;

/**
 * @internal
 */
final class ItemTest extends TestCase
{
    use DroppableTestCase;

    protected function createDroppable(): DroppableInterface
    {
        return new Item([]);
    }
}
