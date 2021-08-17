<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Http;

use Closure;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

final class Request extends GuzzleRequest
{
    public Closure $callback;

    public function __construct(string $uri, callable $parseMethod, string $method = 'GET')
    {
        parent::__construct($method, $uri);

        $this->callback = Closure::fromCallable($parseMethod);
    }
}
