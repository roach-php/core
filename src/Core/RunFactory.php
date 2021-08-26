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

namespace RoachPHP\Core;

use Psr\Container\ContainerInterface;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Downloader\Middleware\DownloaderMiddlewareAdapter;
use RoachPHP\Extensions\Extension;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\ResponseProcessing\Handlers\HandlerAdapter;
use RoachPHP\ResponseProcessing\MiddlewareInterface;
use RoachPHP\Spider\SpiderInterface;
use RoachPHP\Support\ConfigurableInterface;

final class RunFactory
{
    private const DEFAULT_EXTENSIONS = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public function __construct(private ContainerInterface $container)
    {
    }

    public function fromSpider(SpiderInterface $spider): Run
    {
        $configuration = $spider->loadConfiguration();

        return new Run(
            $spider->getInitialRequests(),
            $this->buildDownloaderMiddleware($configuration->downloaderMiddleware),
            $this->buildItemPipeline($configuration->itemProcessors),
            $this->buildResponseMiddleware($configuration->spiderMiddleware),
            $configuration->concurrency,
            $configuration->requestDelay,
            $this->buildExtensions($configuration->extensions)
        );
    }

    /**
     * @psalm-param class-string<DownloaderMiddlewareInterface>[] $downloaderMiddleware
     *
     * @return DownloaderMiddlewareInterface[]
     */
    private function buildDownloaderMiddleware(array $downloaderMiddleware): array
    {
        return \array_map(function (string|array $middleware) {
            return new DownloaderMiddlewareAdapter($this->buildConfigurable($middleware));
        }, $downloaderMiddleware);
    }

    /**
     * @psalm-param array<class-string<ItemProcessorInterface>> $processors
     *
     * @return ItemProcessorInterface[]
     */
    private function buildItemPipeline(array $processors): array
    {
        return \array_map([$this, 'buildConfigurable'], $processors);
    }

    /**
     * @psalm-param array<class-string<MiddlewareInterface>> $handlers
     *
     * @return MiddlewareInterface[]
     */
    private function buildResponseMiddleware(array $handlers): array
    {
        return \array_map(function (string|array $handler) {
            return new HandlerAdapter($this->buildConfigurable($handler));
        }, $handlers);
    }

    /**
     * @template T of ConfigurableInterface
     * @psalm-param class-string<T>|array{class-string<T>, array} $configurable
     *
     * @return T
     */
    private function buildConfigurable(string|array $configurable): mixed
    {
        if (!\is_array($configurable)) {
            $configurable = [$configurable, []];
        }

        [$class, $options] = $configurable;

        /** @psalm-var T $instance */
        $instance = $this->container->get($class);
        $instance->configure($options);

        return $instance;
    }

    /**
     * @psalm-param class-string<Extension>[] $extensions
     *
     * @return Extension[]
     */
    private function buildExtensions(array $extensions): array
    {
        return array_map(
            /** @psalm-suppress MixedInferredReturnType, MixedReturnStatement */
            fn (string $extension): Extension => $this->container->get($extension),
            array_merge(self::DEFAULT_EXTENSIONS, $extensions)
        );
    }
}
