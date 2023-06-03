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

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RoachPHP\Downloader\Proxy\ArrayConfigurationLoader;
use RoachPHP\Downloader\Proxy\ConfigurationLoaderInterface;
use RoachPHP\Downloader\Proxy\Proxy;
use RoachPHP\Http\Request;
use RoachPHP\Support\Configurable;

final class ProxyMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    private ?Proxy $proxy = null;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleRequest(Request $request): Request
    {
        if (null === $this->proxy) {
            $this->logger->warning(
                '[ProxyMiddleware] No proxy configured for middleware',
            );

            return $request;
        }

        $options = $this->proxy->optionsFor($request);

        if ($options->isEmpty()) {
            return $request;
        }

        $this->logger->info(
            '[ProxyMiddleware] Using proxy for request',
            $options->toArray(),
        );

        return $request->addOption('proxy', $options->toArray());
    }

    private function defaultOptions(): array
    {
        return [
            'proxy' => [],
            'loader' => null,
        ];
    }

    private function onAfterConfigured(): void
    {
        /** @var null|class-string<ConfigurationLoaderInterface> $loaderClass */
        $loaderClass = $this->option('loader');

        if (null !== $loaderClass) {
            /** @var ConfigurationLoaderInterface $loader */
            $loader = $this->container->get($loaderClass);
        } else {
            /** @var array<string, array{http?: string, https?: string, no?: array<int, string>}|string>|string $options */
            $options = $this->option('proxy');
            $loader = new ArrayConfigurationLoader($options);
        }

        $this->proxy = $loader->loadProxyConfiguration();
    }
}
