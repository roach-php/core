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

namespace RoachPHP\Scheduling\Timing;

use DateTimeImmutable;
use ErrorException;

final class RealClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function sleep(int $seconds): void
    {
        \sleep($seconds);
    }

    public function sleepUntil(DateTimeImmutable $date): void
    {
        $now = $this->now();

        if ($now >= $date) {
            return;
        }

        try {
            @\time_sleep_until($date->getTimestamp());
        } catch (ErrorException) {
            return;
        }
    }
}
