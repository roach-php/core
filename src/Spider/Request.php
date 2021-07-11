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

namespace Sassnowski\Roach\Spider;

final class Request extends \GuzzleHttp\Psr7\Request
{
    private bool $dropped = false;

    public function __construct(string $uri, string $method, public string $parseMethod = 'parse')
    {
        parent::__construct($method, $uri);
    }

    public function drop(): void
    {
        $this->dropped = true;
    }

    public function wasDropped(): bool
    {
        return $this->dropped;
    }
}
