<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Pavlo Komarov
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader\Middleware;

use RoachPHP\Exception\Exception;
use RoachPHP\Support\ConfigurableInterface;

interface ExceptionMiddlewareInterface extends ConfigurableInterface
{
    public function handleException(Exception $exception): Exception;
}
