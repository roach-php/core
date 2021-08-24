<?php declare(strict_types=1);

namespace RoachPHP\Tests\Extensions;

use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Extensions\Extension;
use Symfony\Contracts\EventDispatcher\Event;

abstract class ExtensionTestCase extends TestCase
{
    private FakeDispatcher $dispatcher;

    protected Extension $extension;

    abstract protected function createExtension(): Extension;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher();
        $this->extension = $this->createExtension();

        /** @var array{string, int} $handler */
        foreach ($this->extension::getSubscribedEvents() as $eventName => $handler) {
            $this->dispatcher->listen($eventName, [$this->extension, $handler[0]]);
        }
    }

    protected function dispatch(Event $event, string $eventName): void
    {
        $this->dispatcher->dispatch($event, $eventName);
    }
}
