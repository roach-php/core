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

namespace RoachPHP\Http;

interface ClientInterface
{
    /**
     * @param Request[] $requests
     * @param ?callable(Response): void $onFulfilled
     */
    public function pool(array $requests, ?callable $onFulfilled = null): void;
}
