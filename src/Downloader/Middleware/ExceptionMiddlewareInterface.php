<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 Auke Geerts
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader\Middleware;

use Exception;
use RoachPHP\Http\Request;
use RoachPHP\Support\ConfigurableInterface;

interface ExceptionMiddlewareInterface extends ConfigurableInterface
{
    public function handleException(Exception $exception, Request $request): ?Request;
}
