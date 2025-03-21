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

namespace RoachPHP\Downloader\Middleware;

use RoachPHP\Http\Request;
use RoachPHP\Support\Configurable;

final class UserAgentMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    public function handleRequest(Request $request): Request
    {
        /** @phpstan-ignore argument.type */
        return $request->addHeader('User-Agent', $this->option('userAgent'));
    }

    private function defaultOptions(): array
    {
        return [
            'userAgent' => 'roach-php',
        ];
    }
}
