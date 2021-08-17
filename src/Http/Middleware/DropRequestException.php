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

use Exception;
use Sassnowski\Roach\Http\Request;

final class DropRequestException extends Exception
{
    public function __construct(public Request $request)
    {
        parent::__construct('Dropping request ' . $this->request->getUri());
    }
}
