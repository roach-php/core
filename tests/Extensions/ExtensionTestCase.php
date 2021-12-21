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
use RoachPHP\Extensions\ExtensionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
abstract class ExtensionTestCase extends TestCase
{
    protected ExtensionInterface $extension;
    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
        $this->extension = $this->createExtension();

        $this->dispatcher->addSubscriber($this->extension);
    }

    abstract protected function createExtension(): ExtensionInterface;

    protected function dispatch(Event $event, string $eventName): void
    {
        $this->dispatcher->dispatch($event, $eventName);
    }
}
