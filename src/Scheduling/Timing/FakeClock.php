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

use DateInterval;
use DateTimeImmutable;

final class FakeClock implements ClockInterface
{
    private int $secondsPassed = 0;

    private DateTimeImmutable $now;

    public function __construct()
    {
        $this->now = new DateTimeImmutable();
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function sleep(int $seconds): void
    {
        $this->sleepUntil(
            $this->now->add(new DateInterval("PT{$seconds}S")),
        );
    }

    public function sleepUntil(DateTimeImmutable $date): void
    {
        if ($date < $this->now) {
            return;
        }

        $this->secondsPassed += $this->now->diff($date)->s;
        $this->now = $date;
    }

    public function timePassed(): int
    {
        return $this->secondsPassed;
    }
}
