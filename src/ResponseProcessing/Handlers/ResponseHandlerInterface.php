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

use RoachPHP\Http\Response;
use RoachPHP\Support\ConfigurableInterface;

interface ResponseHandlerInterface extends ConfigurableInterface
{
    /**
     * Handles a response before the parse callback gets
     * invoked.
     */
    public function handleResponse(Response $response): Response;
}
