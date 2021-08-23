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

namespace RoachPHP\Downloader\Middleware;

use RoachPHP\Http\Request;

final class UserAgentMiddleware extends DownloaderMiddleware implements RequestMiddlewareInterface
{
    protected string $agent;

    public function __construct()
    {
        parent::__construct(['userAgent' => 'roach-php']);
    }

    public function handleRequest(Request $request): Request
    {
        return $request->addHeader('User-Agent', $this->options['userAgent']);
    }
}
