<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Spider;

use RoachPHP\Spider\Middleware\ItemMiddlewareInterface;
use RoachPHP\Spider\Middleware\RequestMiddlewareInterface;
use RoachPHP\Spider\Middleware\ResponseMiddlewareInterface;

interface SpiderMiddlewareInterface extends ItemMiddlewareInterface, RequestMiddlewareInterface, ResponseMiddlewareInterface
{
}
