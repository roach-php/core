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

namespace RoachPHP\Extensions;

use Psr\Log\LoggerInterface;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;

final class LoggerExtension extends Extension
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RunStarting::NAME => ['onRunStarting', 0],
            RunFinished::NAME => ['onRunFinished', 0],
            RequestSending::NAME => ['onRequestSending', 0],
            RequestDropped::NAME => ['onRequestDropped', 0],
            ItemScraped::NAME => ['onItemScraped', 0],
            ItemDropped::NAME => ['onItemDropped', 0],
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
}
