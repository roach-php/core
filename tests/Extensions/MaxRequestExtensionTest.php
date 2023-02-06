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

namespace RoachPHP\Tests\Extensions;

use Generator;
use RoachPHP\Events\RequestScheduling;
use RoachPHP\Events\RequestSending;
use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Extensions\MaxRequestExtension;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class MaxRequestExtensionTest extends ExtensionTestCase
{
    use InteractsWithRequestsAndResponses;

    /**
     * @dataProvider thresholdProvider
     */
    public function testDontDropRequestIfThresholdNotReachedYet(int $threshold): void
    {
        $this->extension->configure(['limit' => $threshold]);

        for ($i = 0; $threshold - 1 > $i; ++$i) {
            $this->dispatch(
                new RequestSending($this->makeRequest()),
                RequestSending::NAME,
            );
        }

        $event = new RequestScheduling($this->makeRequest());
        $this->dispatch($event, RequestScheduling::NAME);

        self::assertFalse($event->request->wasDropped());
    }

    /**
     * @dataProvider thresholdProvider
     */
    public function testDropRequestAfterThresholdWasReached(int $threshold): void
    {
        $this->extension->configure(['limit' => $threshold]);

        for ($i = 0; $i < $threshold; ++$i) {
            $this->dispatch(
                new RequestSending($this->makeRequest()),
                RequestSending::NAME,
            );
        }

        $event = new RequestScheduling($this->makeRequest());
        $this->dispatch($event, RequestScheduling::NAME);

        self::assertTrue($event->request->wasDropped());
    }

    public static function thresholdProvider(): Generator
    {
        yield [1];

        yield [2];

        yield [3];

        yield [4];
    }

    protected function createExtension(): ExtensionInterface
    {
        return new MaxRequestExtension();
    }
}
