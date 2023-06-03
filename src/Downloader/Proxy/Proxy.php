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

namespace RoachPHP\Downloader\Proxy;

use RoachPHP\Http\Request;

final class Proxy
{
    /**
     * @param array<string, ProxyOptions> $proxyList
     */
    public function __construct(private readonly array $proxyList = [])
    {
    }

    public function optionsFor(Request $request): ProxyOptions
    {
        $host = $request->url->host;

        if (null === $host) {
            return ProxyOptions::make();
        }

        if (\array_key_exists($host, $this->proxyList)) {
            return $this->proxyList[$host];
        }

        if (\array_key_exists('*', $this->proxyList)) {
            return $this->proxyList['*'];
        }

        return ProxyOptions::make();
    }
}
