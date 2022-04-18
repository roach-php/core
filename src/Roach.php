<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
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
use RoachPHP\Core\Run;
use RoachPHP\Core\RunFactory;
use RoachPHP\Http\Client;
use RoachPHP\Http\ClientInterface;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Scheduling\Timing\ClockInterface;
use RoachPHP\Scheduling\Timing\SystemClock;
use RoachPHP\Shell\Resolver\NamespaceResolverInterface;
use RoachPHP\Shell\Resolver\StaticNamespaceResolver;
use RoachPHP\Spider\Configuration\Overrides;
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

    /**
     * Start the spider run without collecting scraped items.
     *
     * @psalm-param class-string<SpiderInterface> $spiderClass
     */
    public static function startSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): void
    {
        $engine = self::resolve(Engine::class);

        $run = self::createRun($spiderClass, $overrides, $context);

        $engine->start($run);
    }

    /**
     * Start the spider run and collect and return scraped items.
     *
     * @psalm-param class-string<SpiderInterface> $spiderClass
     *
     * @return ItemInterface[]
     */
    public static function collectSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): array
    {
        $engine = self::resolve(Engine::class);

        $run = self::createRun($spiderClass, $overrides, $context);

        return $engine->collect($run);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $class
     * @psalm-suppress MixedInferredReturnType
     *
     * @return T
     */
    public static function resolve(string $class): mixed
    {
        if (null === self::$container) {
            self::$container = self::defaultContainer();
        }

        /** @psalm-suppress MixedReturnStatement */
        return self::$container->get($class);
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
        $container->add(ClockInterface::class, SystemClock::class);
        $container->add(
            RequestSchedulerInterface::class,
            /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType */
            static fn (): RequestSchedulerInterface => $container->get(ArrayRequestScheduler::class),
        );
        $container->add(ClientInterface::class, Client::class);
        $container->add(
            ItemPipelineInterface::class,
            /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType */
            static fn (): ItemPipelineInterface => $container->get(ItemPipeline::class),
        );
        $container->add(NamespaceResolverInterface::class, StaticNamespaceResolver::class);

        return $container;
    }

    /**
     * @psalm-param class-string<SpiderInterface> $spiderClass
     */
    private static function createRun(string $spiderClass, ?Overrides $overrides, array $context): Run
    {
        self::$container ??= self::defaultContainer();

        $spider = self::resolve($spiderClass);
        $spider->withContext($context);
        $runFactory = new RunFactory(self::$container);

        return $runFactory->fromSpider($spider, $overrides);
    }
}
