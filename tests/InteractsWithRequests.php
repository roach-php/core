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

namespace Sassnowski\Roach\Tests;

use Closure;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

trait InteractsWithRequests
{
    private function createRequest(string $url = '::url::', ?Closure $callback = null): Request
    {
        $callback ??= static function (Response $response): void {
        };

        return new Request($url, $callback);
    }
}
