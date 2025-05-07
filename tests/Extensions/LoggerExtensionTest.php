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

namespace RoachPHP\Tests\Extensions;

use Exception;
use RoachPHP\Core\Run;
use RoachPHP\Events\ExceptionReceived;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

/**
 * @internal
 */
final class LoggerExtensionTest extends ExtensionTestCase
{
    use InteractsWithRequestsAndResponses;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private FakeLogger $logger;

    public function testLogWhenRunStarts(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Run starting'),
        );

        $this->dispatch(new RunStarting(new Run([], '::namespace::')), RunStarting::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Run starting'),
        );
    }

    public function testLogWhenRunFinished(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Run finished'),
        );

        $this->dispatch(new RunFinished(new Run([], '::namespace::')), RunFinished::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Run finished'),
        );
    }

    public function testLogWhenItemGotDropped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Item dropped'),
        );

        $item = (new Item(['foo' => 'bar']))->drop('::reason::');
        $this->dispatch(new ItemDropped($item), ItemDropped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Item dropped', [
                'item' => $item->all(),
                'reason' => '::reason::',
            ]),
        );
    }

    public function testLogWhenRequestWasDropped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Request dropped'),
        );

        $request = $this->makeRequest('::request-url::')->drop('::reason::');
        $this->dispatch(new RequestDropped($request), RequestDropped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Request dropped', [
                'uri' => '::request-url::',
                'reason' => '::reason::',
            ]),
        );
    }

    public function testLogWhenRequestWasSent(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Dispatching request'),
        );

        $request = $this->makeRequest('::request-url::');
        $this->dispatch(new RequestSending($request), RequestSending::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Dispatching request', [
                'uri' => '::request-url::',
            ]),
        );
    }

    public function testLogWhenItemWasScraped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Item scraped'),
        );

        $item = new Item(['foo' => 'bar']);
        $this->dispatch(new ItemScraped($item), ItemScraped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Item scraped', ['foo' => 'bar']),
        );
    }

    public function testLogWhenExceptionWasReceived(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('warning', 'Exception received'),
        );

        $exception = new Exception();
        $this->dispatch(new ExceptionReceived($exception), ExceptionReceived::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('warning', 'Exception received', ['exception' => $exception]),
        );
    }

    protected function createExtension(): ExtensionInterface
    {
        $this->logger = new FakeLogger();

        return new LoggerExtension($this->logger);
    }
}
