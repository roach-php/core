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

final class DropRequest
{
    private bool $dropped = false;

    public function __construct(private Request $request)
    {
    }

    public function __invoke(): Request
    {
        $this->dropped = true;

        return $this->request;
    }

    public function dropped(): bool
    {
        return $this->dropped;
    }
}
