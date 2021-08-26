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

namespace RoachPHP\Tests\Extensions;

use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Extensions\Extension;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
abstract class ExtensionTestCase extends TestCase
{
    protected Extension $extension;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
        $this->extension = $this->createExtension();

        /** @var array{string, int} $handler */
        foreach ($this->extension::getSubscribedEvents() as $eventName => $handler) {
            $this->dispatcher->listen($eventName, [$this->extension, $handler[0]]);
        }
    }

    abstract protected function createExtension(): Extension;

    protected function dispatch(Event $event, string $eventName): void
    {
        $this->dispatcher->dispatch($event, $eventName);
    }
}
