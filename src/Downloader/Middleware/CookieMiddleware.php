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

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

final class CookieMiddleware implements DownloaderMiddlewareInterface
{
    use Configurable;
    private CookieJarInterface $cookieJar;

    public function __construct(?CookieJarInterface $cookieJar = null)
    {
        $this->cookieJar = $cookieJar ?: new CookieJar();
    }

    public function handleRequest(Request $request): Request
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $request->withPsrRequest(
            fn (GuzzleRequest $guzzleRequest) => $this->cookieJar->withCookieHeader($guzzleRequest),
        );
    }

    public function handleResponse(Response $response): Response
    {
        $this->cookieJar->extractCookies(
            $response->getRequest()->getPsrRequest(),
            $response->getResponse(),
        );

        return $response;
    }
}
