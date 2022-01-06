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

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\Http\Response;
use RoachPHP\Spider\Middleware\ResponseMiddlewareInterface;
use RoachPHP\Support\Configurable;

final class ResponseSpiderMiddleware implements ResponseMiddlewareInterface
{
    use Configurable;

    public function handleResponse(Response $response): Response
    {
        return $response;
    }
}
