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

namespace Sassnowski\Roach;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http\Client;
use Sassnowski\Roach\Http\ClientInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Middleware\RequestMiddlewareInterface;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Queue\RequestQueue;
use Sassnowski\Roach\Spider\AbstractSpider;

final class Roach
{
    private static ?ContainerInterface $container = null;

    public static function useContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function startSpider(string $spiderClass): void
    {
        $container = self::$container ?: self::defaultContainer();

        /** @var AbstractSpider $spider */
        $spider = $container->get($spiderClass);
        $middleware = self::buildMiddleware($spider, $container);
        $itemPipeline = self::buildItemPipeline($spider, $container);

        /** @var Engine $engine */
        $engine = $container->get(Engine::class);

        $engine->start($spider->startRequests(), $middleware, $itemPipeline);
    }

    private static function buildMiddleware(
        AbstractSpider $spider,
        ContainerInterface $container,
    ): MiddlewareStack {
        $handlers = \array_map(static function (string|array $middleware) use ($container) {
            if (!\is_array($middleware)) {
                $middleware = [$middleware, []];
            }

            [$class, $options] = $middleware;

            /** @var RequestMiddlewareInterface $instance */
            $instance = $container->get($class);
            $instance->configure($options);

            return $instance;
        }, $spider->middleware());

        return MiddlewareStack::create(...$handlers);
    }

    private static function buildItemPipeline(
        AbstractSpider $spider,
        ContainerInterface $container,
    ): ItemPipelineInterface {
        /** @var ItemPipelineInterface $pipeline */
        $pipeline = $container->get(ItemPipelineInterface::class);
        $processors = \array_map(
            static fn (string $processor) => $container->get($processor),
            $spider->processors(),
        );

        return $pipeline->setProcessors(...$processors);
    }

    private static function defaultContainer(): ContainerInterface
    {
        $container = (new Container())->delegate(new ReflectionContainer());

        $container->share(
            LoggerInterface::class,
            static fn () => (new Logger('roach'))->pushHandler(new StreamHandler('php://stdout')),
        );
        $container->add(RequestQueue::class, ArrayRequestQueue::class);
        $container->add(ClientInterface::class, Client::class);
        $container->share(ItemPipelineInterface::class, ImmutableItemPipeline::class);

        return $container;
    }
}
