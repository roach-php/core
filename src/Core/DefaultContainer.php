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

namespace RoachPHP\Core;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RoachPHP\Http\Client;
use RoachPHP\Http\ClientInterface;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Scheduling\Timing\ClockInterface;
use RoachPHP\Scheduling\Timing\SystemClock;
use RoachPHP\Shell\Resolver\NamespaceResolverInterface;
use RoachPHP\Shell\Resolver\StaticNamespaceResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DefaultContainer implements ContainerInterface
{
    private Container $container;

    public function __construct()
    {
        $this->container = (new Container())->delegate(new ReflectionContainer());

        $this->registerDefaultBindings();
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    private function registerDefaultBindings(): void
    {
        $this->container->addShared(
            ContainerInterface::class,
            $this->container,
        );
        $this->container->addShared(
            LoggerInterface::class,
            static fn () => (new Logger('roach'))->pushHandler(new StreamHandler('php://stdout')),
        );
        $this->container->addShared(EventDispatcher::class, EventDispatcher::class);
        $this->container->addShared(EventDispatcherInterface::class, EventDispatcher::class);
        $this->container->add(ClockInterface::class, SystemClock::class);
        $this->container->add(
            RequestSchedulerInterface::class,
            /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType */
            fn (): RequestSchedulerInterface => $this->container->get(ArrayRequestScheduler::class),
        );
        $this->container->add(ClientInterface::class, Client::class);
        $this->container->add(
            ItemPipelineInterface::class,
            /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType */
            fn (): ItemPipelineInterface => $this->container->get(ItemPipeline::class),
        );
        $this->container->add(NamespaceResolverInterface::class, StaticNamespaceResolver::class);
        $this->container->add(
            EngineInterface::class,
            /** @psalm-suppress MixedReturnStatement, MixedInferredReturnType */
            fn (): EngineInterface => $this->container->get(Engine::class),
        );
        $this->container->add(
            RunnerInterface::class,
            /** @psalm-suppress MixedArgument */
            fn (): RunnerInterface => new Runner($this->container, $this->container->get(EngineInterface::class)),
        );
    }
}
