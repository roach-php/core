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

namespace Sassnowski\Roach\Http\Middleware;

use Sassnowski\Roach\Http\Request;

abstract class RequestMiddleware implements RequestMiddlewareInterface
{
    /**
     * @throws DropRequestException
     */
    protected function dropRequest(Request $request): void
    {
        throw new DropRequestException($request);
    }
}
