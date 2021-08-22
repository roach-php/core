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

namespace Sassnowski\Roach\Spider;

use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Spider\Configuration\Configuration;

interface SpiderInterface
{
    public function loadConfiguration(): Configuration;

    /**
     * @return Request[]
     */
    public function getInitialRequests(): array;
}
