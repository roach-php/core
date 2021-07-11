<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach;

use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sassnowski\Roach\Middleware\LoggerMiddleware;
use Sassnowski\Roach\Middleware\RequestDeduplicationMiddleware;
use Sassnowski\Roach\Middleware\StatsMiddleware;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Scheduler\Scheduler;
use Sassnowski\Roach\Spider\AbstractSpider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use const STDOUT;

final class EngineBuilder
{
    private Logger $logger;

    private array $logHandlers = [];

    private ?Client $client = null;

    private ?Scheduler $scheduler = null;

    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(private AbstractSpider $spider)
    {
        $spiderClass = $spider::class;
        $this->logger = new Logger($spiderClass::$name);
    }

    public static function forSpider(AbstractSpider $spider): self
    {
        return new self($spider);
    }

    public function withClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function withLogHandlers(array $handlers): self
    {
        $this->logHandlers = $handlers;

        return $this;
    }

    public function build(): Engine
    {
        $this->registerLogHandlers();

        $dispatcher = $this->dispatcher ?: new EventDispatcher();

        $this->registerEventSubscribers($dispatcher);

        return new Engine(
            $this->spider,
            $this->scheduler ?: new Scheduler(new ArrayRequestQueue()),
            $dispatcher,
            $this->client ?: new Client(),
        );
    }

    private function registerEventSubscribers(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addSubscriber(new RequestDeduplicationMiddleware());
        $dispatcher->addSubscriber(new StatsMiddleware());
        $dispatcher->addSubscriber(new LoggerMiddleware($this->logger));
    }

    private function registerLogHandlers(): void
    {
        if (!empty($this->logHandlers)) {
            foreach ($this->logHandlers as $handler) {
                $this->logger->pushHandler($handler);
            }

            return;
        }

        $this->logger->pushHandler(
            new StreamHandler(STDOUT),
        );
    }
}
