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

namespace RoachPHP\Tests\Downloader\Proxy;

use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Proxy\Proxy;
use RoachPHP\Downloader\Proxy\ProxyOptions;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class ProxyTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testReturnsEmptyProxyOptionsIfNoConfigurationExistsForRequestDomain(): void
    {
        $proxy = new Proxy([]);

        $options = $proxy->optionsFor($this->makeRequest());

        self::assertTrue($options->equals(ProxyOptions::make()));
    }

    public function testReturnMatchingProxyOptionsForRequestIfConfigured(): void
    {
        $proxy = new Proxy([
            'domain-1.com' => ProxyOptions::make()
                ->allProtocols('::proxy-url-1::'),
            'domain-2.com' => ProxyOptions::make()
                ->allProtocols('::proxy-url-2::'),
        ]);

        $options = $proxy->optionsFor(
            $this->makeRequest('https://domain-1.com'),
        );
        self::assertTrue(
            $options->equals(
                ProxyOptions::make()->allProtocols('::proxy-url-1::'),
            ),
        );

        $options = $proxy->optionsFor(
            $this->makeRequest('https://domain-2.com'),
        );
        self::assertTrue(
            $options->equals(
                ProxyOptions::make()->allProtocols('::proxy-url-2::'),
            ),
        );
    }

    public function testReturnsWildcardOptionsIfConfiguredAndDomainDoesntMatch(): void
    {
        $proxy = new Proxy([
            'domain-1.com' => ProxyOptions::make()
                ->allProtocols('::proxy-url-1::'),
            '*' => ProxyOptions::make()
                ->allProtocols('::proxy-url-2::'),
        ]);

        $options = $proxy->optionsFor(
            $this->makeRequest('https://domain-2.com'),
        );
        self::assertTrue(
            $options->equals(
                ProxyOptions::make()->allProtocols('::proxy-url-2::'),
            ),
        );
    }

    public function testPreferDomainConfigurationOverWildcard(): void
    {
        $proxy = new Proxy([
            'domain-1.com' => ProxyOptions::make()
                ->allProtocols('::proxy-url-1::'),
            '*' => ProxyOptions::make()
                ->allProtocols('::proxy-url-2::'),
        ]);

        $options = $proxy->optionsFor(
            $this->makeRequest('https://domain-1.com'),
        );
        self::assertTrue(
            $options->equals(
                ProxyOptions::make()->allProtocols('::proxy-url-1::'),
            ),
        );
    }
}
