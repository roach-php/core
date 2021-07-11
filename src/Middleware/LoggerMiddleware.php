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

namespace Sassnowski\Roach\Middleware;

use Monolog\Logger;
use Sassnowski\Roach\Events\ItemEmitted;
use Sassnowski\Roach\Events\RequestDropped;
use Sassnowski\Roach\Events\RequestSending;
use Sassnowski\Roach\Events\ResponseReceived;
use Sassnowski\Roach\Events\RunFinished;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LoggerMiddleware implements EventSubscriberInterface
{
    public function __construct(private Logger $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestSending::NAME => [
                ['onRequestSending', 0],
            ],
            RequestDropped::NAME => [
                ['onRequestDropped', 0],
            ],
            ResponseReceived::NAME => [
                ['onResponseReceived', 0],
            ],
            ItemEmitted::NAME => [
                ['onItemEmitted', 0],
            ],
            RunFinished::NAME => [
                ['onRunFinished', -10],
            ],
        ];
    }

    public function onRequestSending(RequestSending $event): void
    {
        $request = $event->getRequest();

        $this->logger->debug('Downloading URL', ['url' => (string) $request->getUri()]);
    }

    public function onRequestDropped(RequestDropped $event): void
    {
        $request = $event->getRequest();

        $this->logger->debug(
            'Dropped request',
            ['url' => (string) $request->getUri()],
        );
    }

    public function onResponseReceived(ResponseReceived $event): void
    {
        $request = $event->getRequest();

        $this->logger->debug(
            'Parsing request response',
            ['url' => (string) $request->getUri()],
        );
    }

    public function onItemEmitted(ItemEmitted $event): void
    {
        $this->logger->debug(
            'Emitted item',
            ['item' => $event->getItem()],
        );
    }

    public function onRunFinished(RunFinished $event): void
    {
        $this->logger->debug(
            'Run finished',
            [$event->getStats()],
        );
    }
}
