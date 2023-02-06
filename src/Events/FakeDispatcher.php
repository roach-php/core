<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Events;

use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class FakeDispatcher extends EventDispatcher
{
    /**
     * @var array<string, object[]>
     */
    private array $dispatchedEvents = [];

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $eventName ??= \get_class($event);

        parent::dispatch($event, $eventName);

        $this->dispatchedEvents[$eventName][] = $event;

        return $event;
    }

    public function assertDispatched(string $eventName, ?callable $callback = null): void
    {
        Assert::assertArrayHasKey($eventName, $this->dispatchedEvents);

        if (null !== $callback) {
            foreach ($this->dispatchedEvents[$eventName] as $event) {
                if ($callback($event)) {
                    return;
                }
            }

            Assert::fail('Event was not dispatched with correct payload');
        }
    }

    public function assertNotDispatched(string $eventName): void
    {
        Assert::assertArrayNotHasKey($eventName, $this->dispatchedEvents);
    }

    public function listen(string $eventName, callable $listener): void
    {
        $this->addListener($eventName, $listener);
    }
}
