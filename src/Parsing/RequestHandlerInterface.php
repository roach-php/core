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

namespace Sassnowski\Roach\Parsing;

use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

interface RequestHandlerInterface
{
    /**
     * Handles a request that got emitted while parsing $response.
     */
    public function handleRequest(Request $request, Response $response): Request;
}
