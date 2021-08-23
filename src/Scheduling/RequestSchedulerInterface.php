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

namespace RoachPHP\Scheduling;

use RoachPHP\Http\Request;

interface RequestSchedulerInterface
{
    public function schedule(Request $request): void;

    /**
     * @return Request[]
     */
    public function nextRequests(): array;

    public function empty(): bool;

    public function setBatchSize(int $batchSize): self;

    public function setDelay(int $delay): self;
}
