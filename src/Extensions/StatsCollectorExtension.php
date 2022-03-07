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

namespace RoachPHP\Extensions;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Scheduling\Timing\ClockInterface;
use RoachPHP\Support\Configurable;

final class StatsCollectorExtension implements ExtensionInterface
{
    use Configurable;

    private ?DateTimeImmutable $startTime = null;

    /**
     * @var array{
     *             duration: ?string,
     *             "requests.sent": int,
     *             "requests.dropped": int,
     *             "items.scraped": int,
     *             "items.dropped": int
     *             }
     */
    private array $stats = [
        'duration' => null,
        'requests.sent' => 0,
        'requests.dropped' => 0,
        'items.scraped' => 0,
        'items.dropped' => 0,
    ];

    public function __construct(private LoggerInterface $logger, private ClockInterface $clock)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RunStarting::NAME => ['onRunStarting', 200],
            RequestSending::NAME => ['onRequestSending', 200],
            RequestDropped::NAME => ['onRequestDropped', 200],
            ItemDropped::NAME => ['onItemDropped', 200],
            ItemScraped::NAME => ['onItemScraped', 200],
            RunFinished::NAME => ['onRunFinished', 200],
        ];
    }

    public function onRunStarting(): void
    {
        $this->startTime = $this->clock->now();
    }

    public function onRunFinished(): void
    {
        if (null !== $this->startTime) {
            $duration = $this->startTime->diff($this->clock->now());
            $this->stats['duration'] = $duration->format('%H:%I:%S');
        }

        $this->logger->info('Run statistics', $this->stats);
    }

    public function onRequestSending(RequestSending $event): void
    {
        if (!$event->request->wasDropped()) {
            ++$this->stats['requests.sent'];
        }
    }

    public function onRequestDropped(): void
    {
        ++$this->stats['requests.dropped'];
    }

    public function onItemDropped(): void
    {
        ++$this->stats['items.dropped'];
    }

    public function onItemScraped(): void
    {
        ++$this->stats['items.scraped'];
    }
}
