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

namespace RoachPHP\ResponseProcessing;

use RoachPHP\ResponseProcessing\Handlers\ItemHandlerInterface;
use RoachPHP\ResponseProcessing\Handlers\RequestHandlerInterface;
use RoachPHP\ResponseProcessing\Handlers\ResponseHandlerInterface;

interface MiddlewareInterface extends ItemHandlerInterface, RequestHandlerInterface, ResponseHandlerInterface
{
}
