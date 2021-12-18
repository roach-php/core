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
use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

final class RobotsTxtMiddleware extends DownloaderMiddleware implements RequestMiddlewareInterface
{
    /** @var array<string, Robots> */
    private array $robots = [];

    public function __construct()
    {
        parent::__construct([
            'fileName' => 'robots.txt'
        ]);
    }

    public function handleRequest(Request $request): Request
    {
        /** @var null|string $userAgent */
        $userAgent = $request->getHeader('User-Agent')[0] ?? null;
        $uri = $request->getUri();
        $robotsUrl = $this->createRobotsUrl($uri);

        if (!isset($this->robots[$robotsUrl])) {
            $this->robots[$robotsUrl] = Robots::create($userAgent, $robotsUrl);
        }

        $robots = $this->robots[$robotsUrl];

        if (!$robots->mayIndex($uri, $userAgent)) {
            return $request->drop("robots.txt forbids crawling {$uri} for user agent {$userAgent}");
        }

        return $request;
    }

    private function createRobotsUrl(string $url): string
    {
        $robotsUrl = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);

        if ($port = parse_url($url, PHP_URL_PORT)) {
            $robotsUrl .= ":{$port}";
        }

        return "{$robotsUrl}/{$this->options['fileName']}";
    }
}
