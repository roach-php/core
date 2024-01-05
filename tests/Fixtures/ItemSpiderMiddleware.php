<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\Middleware\ItemMiddlewareInterface;
use RoachPHP\Support\Configurable;

final class ItemSpiderMiddleware implements ItemMiddlewareInterface
{
    use Configurable;

    public function handleItem(ItemInterface $item, Response $response): ItemInterface
    {
        return $item;
    }
}
