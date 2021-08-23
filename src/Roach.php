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

namespace RoachPHP;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RoachPHP\Core\Engine;
use RoachPHP\Core\RunFactory;
use RoachPHP\Extensions\ExtensionsFactory;
use RoachPHP\Http\Client;
use RoachPHP\Http\ClientInterface;
use RoachPHP\ItemPipeline\ImmutableItemPipeline;
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Scheduling\Timing\ClockInterface;
use RoachPHP\Scheduling\Timing\RealClock;
use RoachPHP\Spider\SpiderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

        /** @var SpiderInterface $spider */
        $spider = $container->get($spiderClass);
        $runFactory = new RunFactory($container);

        /** @var Engine $engine */
        $engine = $container->get(Engine::class);
        $run = $runFactory->fromSpider($spider);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(EventDispatcher::class);
        $extensions = (new ExtensionsFactory($container))->buildExtensionsForRun($run);

        foreach ($extensions as $extension) {
            $dispatcher->addSubscriber($extension);
        }

        $engine->start($run);
    }

    private static function defaultContainer(): ContainerInterface
    {
        $container = (new Container())->delegate(new ReflectionContainer());

        $container->share(
            LoggerInterface::class,
            static fn () => (new Logger('roach'))->pushHandler(new StreamHandler('php://stdout')),
        );
        $container->share(EventDispatcher::class, EventDispatcher::class);
        $container->share(EventDispatcherInterface::class, EventDispatcher::class);
        $container->add(ClockInterface::class, RealClock::class);
        $container->add(
            RequestSchedulerInterface::class,
            /** @psalm-suppress MixedInferredReturnType,MixedReturnStatement */
            static fn (): RequestSchedulerInterface => $container->get(ArrayRequestScheduler::class),
        );
        $container->add(ClientInterface::class, Client::class);
        $container->add(
            ItemPipelineInterface::class,
            /** @psalm-suppress MixedInferredReturnType,MixedReturnStatement */
            static fn (): ItemPipelineInterface => $container->get(ImmutableItemPipeline::class),
        );

        return $container;
    }
}
