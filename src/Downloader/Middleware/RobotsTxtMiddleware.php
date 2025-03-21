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
use Spatie\Robots\Robots;

final class RobotsTxtMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    /**
     * @var array<string, Robots>
     */
    private array $robots = [];

    public function handleRequest(Request $request): Request
    {
        /** @var string $userAgent */
        $userAgent = $request->getHeader('User-Agent')[0] ?? '';
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
        $robotsUrl = \parse_url($url, \PHP_URL_SCHEME) . '://' . \parse_url($url, \PHP_URL_HOST);

        $port = \parse_url($url, \PHP_URL_PORT);

        if (null !== $port && false !== $port) {
            $robotsUrl .= ":{$port}";
        }

        /** @var string $fileName */
        $fileName = $this->option('fileName');

        return "{$robotsUrl}/{$fileName}";
    }

    private function defaultOptions(): array
    {
        return [
            'fileName' => 'robots.txt',
        ];
    }
}
