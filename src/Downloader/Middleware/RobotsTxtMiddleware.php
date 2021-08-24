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
use Spatie\Robots\Robots;

final class RobotsTxtMiddleware extends DownloaderMiddleware implements RequestMiddlewareInterface
{
    private Robots $robots;

    public function __construct()
    {
        parent::__construct();

        $this->robots = Robots::create();
    }

    public function handleRequest(Request $request): Request
    {
        /** @var null|string $userAgent */
        $userAgent = $request->getHeader('User-Agent')[0] ?? null;
        $uri = $request->getUri();

        if (!$this->robots->mayIndex($uri, $userAgent)) {
            return $request->drop("robots.txt forbids crawling {$uri} for user agent {$userAgent}");
        }

        return $request;
    }
}
