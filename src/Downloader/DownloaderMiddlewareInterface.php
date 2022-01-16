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

namespace RoachPHP\Downloader;

use RoachPHP\Downloader\Middleware\ExceptionMiddlewareInterface;
use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Downloader\Middleware\ResponseMiddlewareInterface;

interface DownloaderMiddlewareInterface extends
    RequestMiddlewareInterface,
    ResponseMiddlewareInterface,
    ExceptionMiddlewareInterface
{
}
