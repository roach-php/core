<?php

declare(strict_types=1);

namespace Tests\Unit\Roach;

use PHPUnit\Framework\TestCase;
use RoachPHP\Scheduling\TimedRequestScheduler;
use RoachPHP\Scheduling\Timing\FakeClock;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

final class TimedRequestSchedulerTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private TimedRequestScheduler $scheduler;

    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FakeClock();
        $this->scheduler = new TimedRequestScheduler($this->clock);
    }

    public function testEmpty(): void
    {
        $this->assertTrue($this->scheduler->empty());

        $this->scheduler->schedule($this->makeRequest());

        $this->assertFalse($this->scheduler->empty());
    }

    public function testNextRequestsWithNoReadyAt(): void
    {
        $this->scheduler->schedule($this->makeRequest());

        $this->assertCount(1, $this->scheduler->nextRequests(1));
        $this->assertTrue($this->scheduler->empty());
    }

    public function testNextRequestsWithReadyAtInPast(): void
    {
        $pastTime = $this->clock->now()->modify('-5 minutes');
        $request = $this->makeRequest();
        $request = $request->retryAt($pastTime);

        $this->scheduler->schedule($this->makeRequest());

        $this->assertCount(1, $this->scheduler->nextRequests(1));
    }

    public function testNextRequestsWithReadyAtInFuture(): void
    {
        $futureTime = $this->clock->now()->modify('+5 minutes');
        $request = $this->makeRequest();
        $request = $request->retryAt($futureTime);

        $this->scheduler->schedule($request);

        $this->assertCount(0, $this->scheduler->nextRequests(1));
        // The future request should still be in the queue
        $this->assertFalse($this->scheduler->empty());
    }

    public function testRequestsArePrioritizedByReadyAtTime(): void
    {
        // Create several requests with different ready_at times
        $time1 = $this->clock->now()->modify('-5 minutes');
        $time2 = $this->clock->now()->modify('-10 minutes');
        $time3 = $this->clock->now()->modify('-1 minute');

        $request1 = $this->makeRequest()->retryAt($time1);
        $request2 = $this->makeRequest()->retryAt($time2);
        $request3 = $this->makeRequest()->retryAt($time3);

        $this->scheduler->schedule($request1);
        $this->scheduler->schedule($request2);
        $this->scheduler->schedule($request3);

        $requests = $this->scheduler->nextRequests(3);
        $this->assertCount(3, $requests);

        $this->assertSame($request1, $requests[1]);
        $this->assertSame($request2, $requests[0]);
        $this->assertSame($request3, $requests[2]);
    }

    public function testBatchSizeLimitsNumberOfReturnedRequests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->scheduler->schedule($this->makeRequest());
        }

        $this->assertCount(2, $this->scheduler->nextRequests(2));

        $this->assertFalse($this->scheduler->empty());

        $this->assertCount(3, $this->scheduler->nextRequests(10));

        $this->assertTrue($this->scheduler->empty());
    }

    public function testFirstBatchGetsReturnedImmediately(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());

        $this->scheduler->nextRequests(1);

        $this->assertSame(0, $this->clock->timePassed());
    }

    public function testWaitRequiredTimeIfNextBatchIsNotReadyYet(): void
    {
        $this->scheduler->setDelay(5);
        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        $this->scheduler->nextRequests(1);
        $this->assertSame(0, $this->clock->timePassed());

        $this->clock->sleep(2);

        $this->scheduler->nextRequests(1);
        $this->assertSame(5, $this->clock->timePassed());
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
        $this->assertSame(6, $this->clock->timePassed());
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
        $this->assertSame(9, $this->clock->timePassed());
    }

    public function testForceNextRequestsIgnoresDelay(): void
    {
        $this->scheduler->setDelay(5);

        $this->scheduler->schedule($this->makeRequest());
        $this->scheduler->schedule($this->makeRequest());

        // Grab the next request. Since we haven't grabbed any requests before,
        // no time should have passed.
        $this->scheduler->nextRequests(1);
        $this->assertSame(0, $this->clock->timePassed());

        // Force grab the next request. This should ignore the configured delay
        // and immediately return the next request. This means that no time additional
        // time should have passed.
        $this->scheduler->forceNextRequests(1);
        $this->assertSame(0, $this->clock->timePassed());
    }

    public function testFutureRequestsAreRescheduled(): void
    {
        $now = $this->clock->now();
        $futureDate = $now->add(new \DateInterval("PT5M"));

        $request = $this->makeRequest()->retryAt($futureDate);
        $this->scheduler->schedule($request);

        $requests = $this->scheduler->nextRequests(1);
        $this->assertCount(0, $requests);
        $this->assertFalse($this->scheduler->empty());

        $this->clock->sleepUntil($futureDate);

        $requests = $this->scheduler->nextRequests(1);
        $this->assertCount(1, $requests);
        $this->assertTrue($this->scheduler->empty());
    }

    public function testMixedReadyAndFutureRequests(): void
    {
        $readyNow = $this->makeRequest();

        $fiveMinFuture = $this->clock->now()->modify('+5 minutes');
        $readyIn5Min = $this->makeRequest()->retryAt($fiveMinFuture);

        $tenMinFuture = $this->clock->now()->modify('+10 minutes');
        $readyIn10Min = $this->makeRequest()->retryAt($tenMinFuture);

        $this->scheduler->schedule($readyIn5Min);
        $this->scheduler->schedule($readyNow);
        $this->scheduler->schedule($readyIn10Min);

        $requests = $this->scheduler->nextRequests(10);
        $this->assertCount(1, $requests);
        $this->assertSame($readyNow, $requests[0]);

        $this->assertFalse($this->scheduler->empty());
    }
}
