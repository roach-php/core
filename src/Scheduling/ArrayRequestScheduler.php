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

namespace RoachPHP\Scheduling;

use DateInterval;
use DateTimeImmutable;
use RoachPHP\Http\Request;
use RoachPHP\Scheduling\Timing\ClockInterface;

final class ArrayRequestScheduler implements RequestSchedulerInterface
{
    private int $delay = 0;

    /**
     * @var array<Request>
     */
    private array $requests = [];

    private DateTimeImmutable $nextBatchReadyAt;

    public function __construct(private ClockInterface $clock)
    {
        $this->nextBatchReadyAt = $this->clock->now();
    }

    public function schedule(Request $request): void
    {
        $this->requests[] = $request;
    }

    public function empty(): bool
    {
        return empty($this->requests);
    }

    /**
     * @return array<Request>
     */
    public function nextRequests(int $batchSize): array
    {
        $this->clock->sleepUntil($this->nextBatchReadyAt);

        $this->updateNextBatchTime();

        return $this->getNextRequests($batchSize);
    }

    public function forceNextRequests(int $batchSize): array
    {
        return $this->getNextRequests($batchSize);
    }

    public function setDelay(int $delay): RequestSchedulerInterface
    {
        $this->delay = $delay;

        return $this;
    }

    public function setNamespace(string $namespace): RequestSchedulerInterface
    {
        return $this;
    }

    private function updateNextBatchTime(): void
    {
        $this->nextBatchReadyAt = $this->clock->now()->add(new DateInterval("PT{$this->delay}S"));
    }

    /**
     * @return array<Request>
     */
    private function getNextRequests(int $batchSize): array
    {
        return \array_splice($this->requests, 0, $batchSize);
    }
}
