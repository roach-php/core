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

namespace RoachPHP\Tests\Downloader\Middleware;

use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RoachPHP\Downloader\Middleware\ProxyMiddleware;
use RoachPHP\Downloader\Proxy\ConfigurationLoaderInterface;
use RoachPHP\Downloader\Proxy\Proxy;
use RoachPHP\Downloader\Proxy\ProxyOptions;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

/**
 * @internal
 */
final class ProxyMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private ProxyMiddleware $middleware;

    private ContainerInterface $container;

    private FakeLogger $logger;

    protected function setUp(): void
    {
        $this->container = (new Container())
            ->delegate(new ReflectionContainer());
        $this->logger = new FakeLogger();
        $this->middleware = new ProxyMiddleware(
            $this->container,
            $this->logger,
        );
    }

    public function testDoesNotAddProxyOptionsIfNoProxiesWereProvided(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([]);

        $request = $this->middleware->handleRequest($request);
        self::assertArrayNotHasKey('proxy', $request->getOptions());
    }

    public function testAddProxyOptionsForSpecificURL(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([
            'proxy' => [
                'example.com' => [
                    'http' => '::http-proxy::',
                    'https' => '::https-proxy::',
                    'no' => ['::no::'],
                ],
                '::another-url::' => [
                    'http' => '::other-http-proxy::',
                    'https' => '::other-https-proxy::',
                    'no' => [],
                ],
            ],
        ]);

        $request = $this->middleware->handleRequest($request);
        self::assertArrayHasKey('proxy', $request->getOptions());
        self::assertSame(
            [
                'http' => '::http-proxy::',
                'https' => '::https-proxy::',
                'no' => ['::no::'],
            ],
            $request->getOptions()['proxy'],
        );
    }

    #[DataProvider('requestURLProvider')]
    public function testAddWildCardProxyToAllRequests(string $url): void
    {
        $request = $this->makeRequest($url);
        $this->middleware->configure([
            'proxy' => [
                '*' => [
                    'http' => '::http-proxy::',
                    'https' => '::https-proxy::',
                    'no' => ['::no::'],
                ],
            ],
        ]);

        $request = $this->middleware->handleRequest($request);
        self::assertArrayHasKey('proxy', $request->getOptions());
        self::assertSame(
            [
                'http' => '::http-proxy::',
                'https' => '::https-proxy::',
                'no' => ['::no::'],
            ],
            $request->getOptions()['proxy'],
        );
    }

    public static function requestURLProvider(): array
    {
        return [
            ['https://example.com'],
            ['https://lorem-ipsum.com'],
            ['https://google.com'],
        ];
    }

    public function testPreferURLSpecificProxyToWildcardProxy(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([
            'proxy' => [
                '*' => [
                    'http' => '::wildcard-http-proxy::',
                    'https' => '::wildcard-https-proxy::',
                    'no' => ['::wildcard-no::'],
                ],
                'example.com' => [
                    'http' => '::example-http-proxy::',
                    'https' => '::example-https-proxy::',
                    'no' => ['::example-no::'],
                ],
            ],
        ]);

        $request = $this->middleware->handleRequest($request);
        self::assertArrayHasKey('proxy', $request->getOptions());
        self::assertSame(
            [
                'http' => '::example-http-proxy::',
                'https' => '::example-https-proxy::',
                'no' => ['::example-no::'],
            ],
            $request->getOptions()['proxy'],
        );
    }

    public function testLogProxyOptionsForRequest(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([
            'proxy' => [
                'example.com' => [
                    'http' => '::http-proxy::',
                    'https' => '::https-proxy::',
                    'no' => ['::no::'],
                ],
            ],
        ]);

        $this->middleware->handleRequest($request);

        self::assertTrue(
            $this->logger->messageWasLogged(
                'info',
                '[ProxyMiddleware] Using proxy for request',
                [
                    'http' => '::http-proxy::',
                    'https' => '::https-proxy::',
                    'no' => ['::no::'],
                ],
            ),
        );
    }

    public function testUseCustomConfigurationLoaderClassIfProvided(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->container->add(FakeLoader::class, FakeLoader::class);
        $this->middleware->configure([
            'loader' => FakeLoader::class,
        ]);

        $request = $this->middleware->handleRequest($request);
        self::assertArrayHasKey('proxy', $request->getOptions());
        self::assertSame(
            [
                'http' => '::url::',
                'https' => '::url::',
            ],
            $request->getOptions()['proxy'],
        );
    }

    public function testLogWarningIfNoProxyWasConfiguredForMiddleware(): void
    {
        $request = $this->makeRequest('https://example.com');

        $this->middleware->handleRequest($request);

        self::assertArrayNotHasKey('proxy', $request->getOptions());
        self::assertTrue(
            $this->logger->messageWasLogged(
                'warning',
                '[ProxyMiddleware] No proxy configured for middleware',
            ),
        );
    }
}

final class FakeLoader implements ConfigurationLoaderInterface
{
    public function loadProxyConfiguration(): Proxy
    {
        return new Proxy([
            '*' => ProxyOptions::allProtocols('::url::'),
        ]);
    }
}
