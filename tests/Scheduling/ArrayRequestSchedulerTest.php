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

namespace RoachPHP\Tests\Scheduling;

use PHPUnit\Framework\TestCase;
use RoachPHP\Scheduling\ArrayRequestScheduler;
use RoachPHP\Scheduling\Timing\FakeClock;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @group queue
 *
 * @internal
 */
final class ArrayRequestSchedulerTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private ArrayRequestScheduler $scheduler;

    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FakeClock();
        $this->scheduler = new ArrayRequestScheduler($this->clock);
    }

    public function testEmpty(): void
    {
        self::assertTrue($this->scheduler->empty());

        $this->scheduler->schedule($this->makeRequest());

        self::assertFalse($this->scheduler->empty());
    }

    /**
     * @dataProvider batchSizeProvider
     */
    public function testGroupScheduledRequestAccordingToBatchSize(int $batchSize, array $expectedBatchSizes): void
    {
        for ($i = 0; 10 > $i; ++$i) {
            $this->scheduler->schedule($this->makeRequest());
        }

        foreach ($expectedBatchSizes as $expectedBatchSize) {
            self::assertCount($expectedBatchSize, $this->scheduler->nextRequests($batchSize));
        }
    }

    public static function batchSizeProvider(): iterable
    {
        yield 'batch size 1' => [
            'batchSize' => 1,
            'expectedRequestCounts' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
        ];

        yield 'batch size 2' => [
            'batchSize' => 2,
            'expectedRequestCounts' => [2, 2, 2, 2, 2, 0],
        ];

        yield 'batch size 3' => [
            'batchSize' => 3,
            'expectedRequestCounts' => [3, 3, 3, 1, 0],
        ];

        yield 'batch size 4' => [
            'batchSize' => 4,
            'expectedRequestCounts' => [4, 4, 2, 0],
        ];

        yield 'batch size 5' => [
            'batchSize' => 5,
            'expectedRequestCounts' => [5, 5, 0],
        ];
    }

    public function testFirstInFirstOut(): void
    {
        $requestA = $this->makeRequest();
        $requestB = $this->makeRequest();
        $requestC = $this->makeRequest();

        $this->scheduler->schedule($requestA);
        $this->scheduler->schedule($requestB);
        $this->scheduler->schedule($requestC);

        self::assertSame($requestA, $this->scheduler->nextRequests(1)[0]);
        self::assertSame($requestB, $this->scheduler->nextRequests(1)[0]);
        self::assertSame($requestC, $this->scheduler->nextRequests(1)[0]);
    }

    public function testFirstBatchGetsReturnedImmediately(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());

        $this->scheduler->nextRequests(1);

        self::assertSame(0, $this->clock->timePassed());
    }

    public function testWaitRequiredTimeIfNextBatchIsNotReadyYet(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        $this->scheduler->nextRequests(1);
        self::assertSame(0, $this->clock->timePassed());

        $this->clock->sleep(2);

        $this->scheduler->nextRequests(1);
        self::assertSame(5, $this->clock->timePassed());
    }

    public function testImmediatelyReturnNextBatchIfMoreTimeThanNecessaryHasPassed(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        $this->scheduler->nextRequests(1);

        $this->clock->sleep(6);
        $this->scheduler->nextRequests(1);

        // No additional time should have passed
        self::assertSame(6, $this->clock->timePassed());
    }

    public function testNextBatchDelayStartsAfterRequestsWereDispatched(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        // Wait some before grabbing next request...
        $this->clock->sleep(4);
        $this->scheduler->nextRequests(1);

        $this->scheduler->nextRequests(1);
        // Delay for a batch does not start when it gets scheduled
        // but after the previous batch was dispatched.
        // This means we should have waited another 5 seconds before
        // returning this batch: the initial 4 seconds plus the
        // configured delay.
        self::assertSame(9, $this->clock->timePassed());
    }

    public function testForceNextRequestIgnoresTheConfiguredRequestDelay(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        // Grab the next request. Since we haven't grabbed any requests before,
        // no time should have passed.
        $this->scheduler->nextRequests(1);
        self::assertSame(0, $this->clock->timePassed());

        // Force grab the next request. This should ignore the configured delay
        // and immediately return the next request. This means that no time additional
        // time should have passed.
        $this->scheduler->forceNextRequests(1);
        self::assertSame(0, $this->clock->timePassed());
    }
}
