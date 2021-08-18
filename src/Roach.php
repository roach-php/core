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

use GuzzleHttp\Client;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http\Middleware\MiddlewareStack;
use Sassnowski\Roach\Http\Middleware\RequestMiddlewareInterface;
use Sassnowski\Roach\ItemPipeline\Pipeline;
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
        $queue = $container->get(RequestQueue::class);
        $logger = $container->get(LoggerInterface::class);

        $engine = new Engine(
            $spider->startRequests(),
            $queue,
            $middleware,
            $itemPipeline,
            new Client(),
            $logger,
        );

        $engine->start();
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
    ): Pipeline {
        $processors = \array_map(
            static fn (string $processor) => $container->get($processor),
            $spider->processors(),
        );

        return new Pipeline($processors);
    }

    private static function defaultContainer(): ContainerInterface
    {
        $container = (new Container())->delegate(new ReflectionContainer());

        $container->share(
            LoggerInterface::class,
            static fn () => (new Logger('roach'))->pushHandler(new StreamHandler('php://stdout')),
        );
        $container->add(RequestQueue::class, ArrayRequestQueue::class);

        return $container;
    }
}
