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

namespace Sassnowski\Roach\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Queue\ArrayRequestQueue;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @group queue
 *
 * @internal
 */
final class ArrayQueueTest extends TestCase
{
    use InteractsWithRequests;

    public function testIsEmptyInitially(): void
    {
        $queue = new ArrayRequestQueue();

        self::assertSame(0, $queue->count());
        self::assertTrue($queue->empty());
    }

    public function testCanBeConstructedWithInitialRequest(): void
    {
        $queue = new ArrayRequestQueue(
            $this->createRequest(),
            $this->createRequest(),
        );

        self::assertSame(2, $queue->count());
        self::assertFalse($queue->empty());
    }

    public function testIsNotEmptyAfterAddingRequest(): void
    {
        $queue = new ArrayRequestQueue();

        $queue->queue($this->createRequest());

        self::assertFalse($queue->empty());
    }

    public function testReturnsNumberOfRequestsInQueue(): void
    {
        $queue = new ArrayRequestQueue();

        $queue->queue($this->createRequest());
        self::assertSame(1, $queue->count());

        $queue->queue($this->createRequest());
        self::assertSame(2, $queue->count());

        $queue->queue($this->createRequest());
        self::assertSame(3, $queue->count());
    }

    public function testReturnAllQueuedRequests(): void
    {
        $requests = [
            $this->createRequest(),
            $this->createRequest(),
            $this->createRequest(),
        ];
        $queue = new ArrayRequestQueue(...$requests);

        $result = $queue->all();

        self::assertEquals($requests, $result);
        self::assertSame(0, $queue->count());
    }
}
