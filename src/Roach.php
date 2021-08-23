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
use Sassnowski\Roach\Core\Engine;
use Sassnowski\Roach\Core\RunFactory;
use Sassnowski\Roach\Extensions\ExtensionsFactory;
use Sassnowski\Roach\Http\Client;
use Sassnowski\Roach\Http\ClientInterface;
use Sassnowski\Roach\ItemPipeline\ImmutableItemPipeline;
use Sassnowski\Roach\ItemPipeline\ItemPipelineInterface;
use Sassnowski\Roach\Scheduling\ArrayRequestScheduler;
use Sassnowski\Roach\Scheduling\RequestSchedulerInterface;
use Sassnowski\Roach\Scheduling\Timing\ClockInterface;
use Sassnowski\Roach\Scheduling\Timing\RealClock;
use Sassnowski\Roach\Spider\SpiderInterface;
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
        $dispatcher = $container->get(EventDispatcherInterface::class);
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
        $container->share(
            EventDispatcherInterface::class,
            static fn () => new EventDispatcher(),
        );
        $container->add(ClockInterface::class, RealClock::class);
        $container->add(
            RequestSchedulerInterface::class,
            static fn () => $container->get(ArrayRequestScheduler::class),
        );
        $container->add(ClientInterface::class, Client::class);
        $container->add(
            ItemPipelineInterface::class,
            static fn () => $container->get(ImmutableItemPipeline::class),
        );

        return $container;
    }
}
