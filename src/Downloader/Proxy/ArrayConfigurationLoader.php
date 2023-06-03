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

final class ArrayConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * @param array<string, array{http?: string, https?: string, no?: array<int, string>}|string>|string $params
     */
    public function __construct(private readonly array|string $params)
    {
    }

    public function loadProxyConfiguration(): Proxy
    {
        if (\is_string($this->params)) {
            return new Proxy([
                '*' => ProxyOptions::allProtocols($this->params),
            ]);
        }

        /** @var array<string, ProxyOptions> $proxyList */
        $proxyList = [];

        foreach ($this->params as $domain => $options) {
            if (\is_string($options)) {
                $proxyList[$domain] = ProxyOptions::allProtocols($options);
            } else {
                $proxyList[$domain] = new ProxyOptions(
                    $options['http'] ?? null,
                    $options['https'] ?? null,
                    $options['no'] ?? [],
                );
            }
        }

        return new Proxy($proxyList);
    }
}
