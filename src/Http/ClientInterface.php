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

namespace RoachPHP\Http;

interface ClientInterface
{
    /**
     * @param list<Request>                     $requests
     * @param ?callable(Response): void         $onFulfilled
     * @param ?callable(RequestException): void $onRejected
     */
    public function pool(
        array $requests,
        ?callable $onFulfilled = null,
        ?callable $onRejected = null,
    ): void;
}
