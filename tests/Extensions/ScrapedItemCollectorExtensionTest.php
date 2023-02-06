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

namespace RoachPHP\Tests\Extensions;

use RoachPHP\Events\ItemScraped;
use RoachPHP\Extensions\ScrapedItemCollectorExtension;
use RoachPHP\ItemPipeline\Item;

/**
 * @internal
 */
final class ScrapedItemCollectorExtensionTest extends ExtensionTestCase
{
    public function testCollectsScrapedItems(): void
    {
        $this->extension->configure([]);

        self::assertEmpty($this->extension->getScrapedItems());

        $item = new Item(['::key::' => '::value::']);
        $this->dispatch(new ItemScraped($item), ItemScraped::NAME);

        self::assertEquals([$item], $this->extension->getScrapedItems());
    }

    protected function createExtension(): ScrapedItemCollectorExtension
    {
        return new ScrapedItemCollectorExtension();
    }
}
