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

namespace Sassnowski\Roach\Core;

use Psr\Container\ContainerInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack as HttpMiddleware;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Parsing\Handlers\HandlerAdapter;
use Sassnowski\Roach\Parsing\MiddlewareStack as ResponseMiddleware;
use Sassnowski\Roach\Spider\AbstractSpider;

final class RunFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function fromSpider(AbstractSpider $spider): Run
    {
        return new Run(
            $spider->startRequests(),
            $this->buildHttpMiddleware($spider->httpMiddleware()),
            $this->buildItemPipeline($spider->processors()),
            $this->buildResponseMiddleware($spider->spiderMiddleware()),
            $spider::$concurrency,
            $spider::$requestDelay,
        );
    }

    private function buildHttpMiddleware(array $handlers): HttpMiddleware
    {
        $handlers = \array_map([$this, 'buildConfigurable'], $handlers);

        return HttpMiddleware::create(...$handlers);
    }

    private function buildItemPipeline(array $processors): ItemPipelineInterface
    {
        /** @var ItemPipelineInterface $pipeline */
        $pipeline = $this->container->get(ItemPipelineInterface::class);

        $processors = \array_map([$this, 'buildConfigurable'], $processors);

        return $pipeline->setProcessors(...$processors);
    }

    private function buildResponseMiddleware(array $handlers): ResponseMiddleware
    {
        $handlers = \array_map(function (string $handler) {
            return new HandlerAdapter($this->buildConfigurable($handler));
        }, $handlers);

        return ResponseMiddleware::create(...$handlers);
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
