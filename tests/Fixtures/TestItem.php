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

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\ItemPipeline\AbstractItem;

final class TestItem extends AbstractItem
{
    protected string $baz = 'baz';

    private string $qux = 'qux';

    public function __construct(public string $foo, public ?string $bar)
    {
    }
}
