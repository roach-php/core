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

use Sassnowski\Roach\Events\ItemEmitted;
use Sassnowski\Roach\Events\RequestDropped;
use Sassnowski\Roach\Events\RequestScheduling;
use Sassnowski\Roach\Events\RequestSent;
use Sassnowski\Roach\Events\RunFinished;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StatsMiddleware implements EventSubscriberInterface
{
    private array $stats = [
        'processed' => 0,
        'dropped' => 0,
        'sent' => 0,
        'emitted' => 0,
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            RequestScheduling::NAME => [
                ['onRequestScheduling', 800],
            ],
            RequestDropped::NAME => [
                ['onRequestDropped', 900],
            ],
            RequestSent::NAME => [
                ['onRequestSent', 900],
            ],
            ItemEmitted::NAME => [
                ['onItemEmitted', 900],
            ],
            RunFinished::NAME => [
                ['onRunFinished', 0],
            ],
        ];
    }

    public function onRequestScheduling(RequestScheduling $event): void
    {
        ++$this->stats['processed'];
    }

    public function onRequestDropped(RequestDropped $event): void
    {
        ++$this->stats['dropped'];
    }

    public function onItemEmitted(ItemEmitted $event): void
    {
        ++$this->stats['emitted'];
    }

    public function onRequestSent(RequestSent $event): void
    {
        ++$this->stats['sent'];
    }

    public function onRunFinished(RunFinished $event): void
    {
        $event->addStats($this->stats);
    }
}
