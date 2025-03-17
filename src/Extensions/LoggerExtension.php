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

namespace RoachPHP\Extensions;

use Psr\Log\LoggerInterface;
use RoachPHP\Events\ExceptionReceived;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Support\Configurable;

final class LoggerExtension implements ExtensionInterface
{
    use Configurable;

    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RunStarting::NAME => ['onRunStarting', 100],
            RunFinished::NAME => ['onRunFinished', 100],
            RequestSending::NAME => ['onRequestSending', 100],
            RequestDropped::NAME => ['onRequestDropped', 100],
            ItemScraped::NAME => ['onItemScraped', 100],
            ItemDropped::NAME => ['onItemDropped', 100],
            ExceptionReceived::NAME => ['onExceptionReceived', 100],
        ];
    }

    public function onRunStarting(RunStarting $event): void
    {
        $this->logger->info('Run starting');
    }

    public function onRunFinished(RunFinished $event): void
    {
        $this->logger->info('Run finished');
    }

    public function onRequestSending(RequestSending $event): void
    {
        $this->logger->info('Dispatching request', [
            'uri' => $event->request->getUri(),
        ]);
    }

    public function onRequestDropped(RequestDropped $event): void
    {
        $request = $event->request;

        $this->logger->info('Request dropped', [
            'uri' => $request->getUri(),
            'reason' => $request->getDropReason(),
        ]);
    }

    public function onItemScraped(ItemScraped $event): void
    {
        $this->logger->info('Item scraped', $event->item->all());
    }

    public function onItemDropped(ItemDropped $event): void
    {
        $this->logger->info('Item dropped', [
            'item' => $event->item->all(),
            'reason' => $event->item->getDropReason(),
        ]);
    }

    public function onExceptionReceived(ExceptionReceived $event): void
    {
        $this->logger->warning('Exception received', [
            'exception' => $event->exception,
        ]);
    }
}
