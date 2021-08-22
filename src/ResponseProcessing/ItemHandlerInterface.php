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

namespace Sassnowski\Roach\ResponseProcessing;

use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\Support\ConfigurableInterface;

interface ItemHandlerInterface extends ConfigurableInterface
{
    /**
     * Handles an item that got emitted while parsing $response.
     */
    public function handleItem(
        ItemInterface $item,
        Response $response,
    ): ItemInterface;
}
