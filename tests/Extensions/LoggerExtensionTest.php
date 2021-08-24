<?php declare(strict_types=1);

namespace RoachPHP\Tests\Extensions;

use RoachPHP\Core\Run;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Extensions\Extension;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Testing\FakeLogger;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;

class LoggerExtensionTest extends ExtensionTestCase
{
    use InteractsWithRequestsAndResponses;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private FakeLogger $logger;

    protected function createExtension(): Extension
    {
        $this->logger = new FakeLogger();

        return new LoggerExtension($this->logger);
    }

    public function testLogWhenRunStarts(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Run starting')
        );

        $this->dispatch(new RunStarting(new Run()), RunStarting::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Run starting')
        );
    }

    public function testLogWhenRunFinished(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Run finished')
        );

        $this->dispatch(new RunFinished(new Run()), RunFinished::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Run finished')
        );
    }

    public function testLogWhenItemGotDropped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Item dropped')
        );

        $item = (new Item(['foo' => 'bar']))->drop('::reason::');
        $this->dispatch(new ItemDropped($item), ItemDropped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Item dropped', [
                'item' => $item->all(),
                'reason' => '::reason::',
            ])
        );
    }

    public function testLogWhenRequestWasDropped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Request dropped')
        );

        $request = $this->makeRequest('::request-url::')->drop('::reason::');
        $this->dispatch(new RequestDropped($request), RequestDropped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Request dropped', [
                'uri' => '::request-url::',
                'reason' => '::reason::',
            ])
        );
    }

    public function testLogWhenRequestWasSent(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Dispatching request')
        );

        $request = $this->makeRequest('::request-url::');
        $this->dispatch(new RequestSending($request), RequestSending::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Dispatching request', [
                'uri' => '::request-url::',
            ])
        );
    }

    public function testLogWhenItemWasScraped(): void
    {
        self::assertFalse(
            $this->logger->messageWasLogged('info', 'Item scraped')
        );

        $item = new Item(['foo' => 'bar']);
        $this->dispatch(new ItemScraped($item), ItemScraped::NAME);

        self::assertTrue(
            $this->logger->messageWasLogged('info', 'Item scraped', ['foo' => 'bar'])
        );
    }
}
