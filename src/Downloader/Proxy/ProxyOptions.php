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

final class ProxyOptions
{
    /**
     * @param array<int, string> $excludedDomains
     */
    public function __construct(
        private readonly ?string $httpProxyURL = null,
        private readonly ?string $httpsProxyURL = null,
        private readonly array $excludedDomains = [],
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * Configure the same proxy URL to be used for HTTP and HTTPS.
     */
    public static function allProtocols(string $url): self
    {
        return new self($url, $url, []);
    }

    /**
     * Configure the proxy URL to be used for requests using HTTP.
     */
    public function http(string $url): self
    {
        return new self($url, $this->httpsProxyURL, $this->excludedDomains);
    }

    /**
     * Configure the proxy URL to be used for requests using HTTPs.
     */
    public function https(string $url): self
    {
        return new self($this->httpProxyURL, $url, $this->excludedDomains);
    }

    /**
     * Configure the domains or TLDs that should not use proxies.
     *
     * @param array<int, string>|string $domains
     */
    public function exclude(array|string $domains): self
    {
        return new self(
            $this->httpProxyURL,
            $this->httpsProxyURL,
            (array) $domains,
        );
    }

    public function isEmpty(): bool
    {
        return null === $this->httpProxyURL
            && null === $this->httpsProxyURL
            && \count($this->excludedDomains) === 0;
    }

    public function equals(self $other): bool
    {
        return $this->httpProxyURL === $other->httpProxyURL
            && $this->httpsProxyURL === $other->httpsProxyURL
            && $this->excludedDomains === $other->excludedDomains;
    }

    /**
     * @return array{
     *     http?: string,
     *     https?: string,
     *     no?: array<int, string>
     * }
     */
    public function toArray(): array
    {
        return \array_filter([
            'http' => $this->httpProxyURL,
            'https' => $this->httpsProxyURL,
            'no' => $this->excludedDomains,
        ]);
    }
}
