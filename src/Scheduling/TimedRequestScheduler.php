<?php

declare(strict_types=1);

namespace RoachPHP\Scheduling;

use RoachPHP\Http\Request;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Scheduling\Timing\ClockInterface;

/**
 * A request scheduler that schedules requests based on their 'readyAt' times.
 * Requests with a future 'readyAt' time are held until that time is reached.
 * Requests with no 'readyAt' time are processed once delay is reached.
 */
class TimedRequestScheduler implements RequestSchedulerInterface
{
    private \SplPriorityQueue $requestQueue;

    private int $delay = 0;

    private \DateTimeImmutable $nextBatchReadyAt;

    public function __construct(private ClockInterface $clock)
    {
        $this->requestQueue = new \SplPriorityQueue();
        $this->nextBatchReadyAt = $this->clock->now();
    }

    public function empty(): bool
    {
        return $this->requestQueue->isEmpty();
    }

    public function schedule(Request $request): void
    {
        $priority = -1 * $this->getReadyAt($request, $this->clock->now())->getTimestamp();

        $this->requestQueue->insert($request, $priority);
    }

    public function nextRequests(int $batchSize): array
    {
        $this->clock->sleepUntil($this->nextBatchReadyAt);

        $this->updateNextBatchTime();

        return $this->getReadyRequests($batchSize);
    }

    public function forceNextRequests(int $batchSize): array
    {
        return $this->getReadyRequests($batchSize);
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        return $this;
    }

    private function updateNextBatchTime(): void
    {
        $this->nextBatchReadyAt = $this->clock->now()->add(new \DateInterval("PT{$this->delay}S"));
    }

    private function getReadyRequests(int $batchSize): array
    {
        $readyRequests = [];
        $delayedRequests = [];

        $now = $this->clock->now();
        $remaining = $batchSize;

        while (!$this->requestQueue->isEmpty() && $remaining > 0) {
            $request = $this->requestQueue->extract();

            $isReady = $now >= $this->getReadyAt($request, $now);

            if ($isReady) {
                $readyRequests[] = $request;
                $remaining--;
            } else {
                $delayedRequests[] = $request;
            }
        }

        foreach ($delayedRequests as $request) {
            $this->schedule($request);
        }

        return $readyRequests;
    }

    private function getReadyAt(Request $request, \DateTimeImmutable $now): \DateTimeImmutable
    {
        if ($readyAt = $request->getReadyAt()) {
            return $readyAt;
        }

        return $now;
    }
}
