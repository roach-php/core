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

namespace RoachPHP\Tests\Events;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;

/**
 * @internal
 */
final class FakeDispatcherTest extends TestCase
{
    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
    }

    public function testAssertDispatchedPassesIfEventWasDispatched(): void
    {
        $event = new FakeEvent();
        $this->dispatcher->dispatch($event, 'event.name');

        $this->dispatcher->assertDispatched('event.name');
    }

    public function testAssertDispatchedFailsIfNoEventWasDispatched(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertDispatched('event.name');
    }

    public function testAssertDispatchedFailsIfCallbackReturnsFalse(): void
    {
        $this->dispatcher->dispatch(new FakeEvent(), 'event.name');

        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertDispatched('event.name', static fn (FakeEvent $event) => false);
    }

    public function testAssertDispatchedPassesIfCallbackReturnsTrue(): void
    {
        $this->dispatcher->dispatch(new FakeEvent(), 'event.name');

        $this->dispatcher->assertDispatched('event.name', static fn (FakeEvent $event) => true);
    }

    public function testAssertNotDispatched(): void
    {
        $event = new FakeEvent();

        $this->dispatcher->assertNotDispatched('event.name');

        $this->dispatcher->dispatch($event, 'event.name');
        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertNotDispatched('event.name');
    }

    public function testRunEventListeners(): void
    {
        $called = false;
        $this->dispatcher->listen('event.name', static function () use (&$called): void {
            $called = true;
        });

        $this->dispatcher->dispatch(new FakeEvent(), 'event.name');

        self::assertTrue($called);
    }
}

final class FakeEvent
{
    public function __construct(public array $data = [])
    {
    }
}
