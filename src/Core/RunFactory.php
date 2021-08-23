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
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\ResponseProcessing\Handlers\HandlerAdapter;
use RoachPHP\ResponseProcessing\MiddlewareInterface;
use RoachPHP\Spider\SpiderInterface;

final class RunFactory
{
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
        );
    }

    /**
     * @return DownloaderMiddlewareInterface[]
     */
    private function buildDownloaderMiddleware(array $downloaderMiddleware): array
    {
        return \array_map(function (string|array $middleware) {
            return new DownloaderMiddlewareAdapter($this->buildConfigurable($middleware));
        }, $downloaderMiddleware);
    }

    private function buildItemPipeline(array $processors): ItemPipelineInterface
    {
        /** @var ItemPipelineInterface $pipeline */
        $pipeline = $this->container->get(ItemPipelineInterface::class);

        $processors = \array_map([$this, 'buildConfigurable'], $processors);

        return $pipeline->setProcessors(...$processors);
    }

    /**
     * @return MiddlewareInterface[]
     */
    private function buildResponseMiddleware(array $handlers): array
    {
        return \array_map(function (string|array $handler) {
            return new HandlerAdapter($this->buildConfigurable($handler));
        }, $handlers);
    }

    private function buildConfigurable(string|array $configurable): mixed
    {
        if (!\is_array($configurable)) {
            $configurable = [$configurable, []];
        }

        [$class, $options] = $configurable;

        $instance = $this->container->get($class);
        $instance->configure($options);

        return $instance;
    }
}
