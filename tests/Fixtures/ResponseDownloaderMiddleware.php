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

use RoachPHP\Downloader\Middleware\ResponseMiddlewareInterface;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

final class ResponseDownloaderMiddleware implements ResponseMiddlewareInterface
{
    use Configurable;

    public function handleResponse(Response $response): Response
    {
        return $response;
    }
}
