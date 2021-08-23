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

namespace RoachPHP\ResponseProcessing\Handlers;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\ConfigurableInterface;

interface RequestHandlerInterface extends ConfigurableInterface
{
    /**
     * Handles a request that got emitted while parsing $response.
     */
    public function handleRequest(Request $request, Response $response): Request;
}
