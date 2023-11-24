<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Scheduling;

use RoachPHP\Http\Request;

interface RequestSchedulerInterface
{
    public function schedule(Request $request): void;

    /**
     * Return the next number of requests as defined by $batchSize as soon
     * as they are ready.
     *
     * @return array<Request>
     */
    public function nextRequests(int $batchSize): array;

    /**
     * Immediately return the next number of requests as defined by $batchSize
     * regardless of the configured delay.
     *
     * @return array<Request>
     */
    public function forceNextRequests(int $batchSize): array;

    public function empty(): bool;

    public function setDelay(int $delay): self;

    public function setNamespace(string $namespace): self;
}
