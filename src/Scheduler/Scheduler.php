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

namespace Sassnowski\Roach\Scheduler;

use Sassnowski\Roach\Queue\RequestQueue;
use Sassnowski\Roach\Spider\Request;

final class Scheduler
{
    public function __construct(private RequestQueue $queue)
    {
    }

    public function hasRequests(): bool
    {
        return $this->queue->count() !== 0;
    }

    public function scheduleRequest(Request $request): void
    {
        $this->queue->enqueue($request);
    }

    public function nextRequests(): array
    {
        return $this->queue->dequeue();
    }
}
