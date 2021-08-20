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

namespace Sassnowski\Roach\Tests\Scheduling\Timing;

use DateInterval;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Scheduling\Timing\ClockInterface;
use Sassnowski\Roach\Scheduling\Timing\FakeClock;

/**
 * @internal
 */
final class FakeClockTest extends TestCase
{
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FakeClock();
    }

    public function testWaitUntilTargetTime(): void
    {
        $now = $this->clock->now();

        $this->clock->sleepUntil($now->add(new DateInterval('PT1S')));
        $then1 = $this->clock->now();
        self::assertSame(1, $now->diff($then1)->s);

        $this->clock->sleepUntil($then1->add(new DateInterval('PT1S')));
        $then2 = $this->clock->now();
        self::assertSame(1, $then1->diff($then2)->s);
        self::assertSame(2, $now->diff($then2)->s);
    }

    public function testDontWaitIfTargetDateIsInPast(): void
    {
        $now = $this->clock->now();

        $this->clock->sleepUntil($now->sub(new DateInterval('PT2S')));
        $then = $this->clock->now();
        self::assertSame(0, $now->diff($then)->s);
    }

    public function testRecordTimePassedSleepUntil(): void
    {
        $clock = new FakeClock();

        self::assertSame(0, $clock->timePassed());

        $clock->sleepUntil($clock->now()->add(new DateInterval('PT5S')));
        self::assertSame(5, $clock->timePassed());

        $clock->sleepUntil($clock->now()->add(new DateInterval('PT2S')));
        self::assertSame(7, $clock->timePassed());

        $clock->sleepUntil($clock->now()->add(new DateInterval('PT3S')));
        self::assertSame(10, $clock->timePassed());
    }

    public function testRecordTimePassedSleep(): void
    {
        $clock = new FakeClock();

        self::assertSame(0, $clock->timePassed());

        $clock->sleep(5);
        self::assertSame(5, $clock->timePassed());

        $clock->sleep(2);
        self::assertSame(7, $clock->timePassed());

        $clock->sleep(3);
        self::assertSame(10, $clock->timePassed());
    }

    protected function createClock(): ClockInterface
    {
        return new FakeClock();
    }
}
