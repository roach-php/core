<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Extensions;

use Generator;
use RoachPHP\Core\Run;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestSending;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Extensions\ExtensionInterface;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Scheduling\Timing\FakeClock;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
final class StatsCollectorExtensionTest extends ExtensionTestCase
{
    use InteractsWithRequestsAndResponses;

    private FakeLogger $logger;

    private FakeClock $clock;

    /**
     * @param array{event: Event, eventName: string, stat: string} $scenario
     *
     * @dataProvider statsScenarioProvider
     */
    public function testCountNumberOfEventOccurrence(array $scenario, int $eventCount): void
    {
        $this->extension->configure([]);

        $this->withRun(function () use ($scenario, $eventCount): void {
            for ($i = 0; $i < $eventCount; ++$i) {
                $this->dispatch($scenario['event'], $scenario['eventName']);
            }
        });

        $this->assertStatWasLogged($scenario['stat'], $eventCount);
    }

    /**
     * @dataProvider runtimeProvider
     */
    public function testLogRuntime(int $seconds, string $expected): void
    {
        $this->extension->configure([]);

        $this->withRun(fn () => $this->clock->sleep($seconds));

        $this->assertStatWasLogged('duration', $expected);
    }

    public function statsScenarioProvider(): Generator
    {
        $scenarios = [
            'items.scraped' => [
                'event' => new ItemScraped(new Item([])),
                'eventName' => ItemScraped::NAME,
                'stat' => 'items.scraped',
            ],

            'items.dropped' => [
                'event' => new ItemDropped(new Item([])),
                'eventName' => ItemDropped::NAME,
                'stat' => 'items.dropped',
            ],

            'requests.sent' => [
                'event' => new RequestSending($this->makeRequest()),
                'eventName' => RequestSending::NAME,
                'stat' => 'requests.sent',
            ],

            'requests.dropped' => [
                'event' => new RequestDropped($this->makeRequest()),
                'eventName' => RequestDropped::NAME,
                'stat' => 'requests.dropped',
            ],
        ];

        foreach ($scenarios as $stat => $scenario) {
            foreach ([0, 1, 5, 15] as $eventCount) {
                yield "{$stat}: {$eventCount}" => [$scenario, $eventCount];
            }
        }
    }

    public function runtimeProvider(): Generator
    {
        yield from [
            ['seconds' => 10, 'expected' => '00:00:10'],
            ['seconds' => 60, 'expected' => '00:01:00'],
            ['seconds' => 90, 'expected' => '00:01:30'],
            ['seconds' => 3600, 'expected' => '01:00:00'],
            ['seconds' => 5400, 'expected' => '01:30:00'],
            ['seconds' => 5423, 'expected' => '01:30:23'],
        ];
    }

    protected function createExtension(): ExtensionInterface
    {
        $this->logger = new FakeLogger();
        $this->clock = new FakeClock();

        return new StatsCollectorExtension($this->logger, $this->clock);
    }

    /**
     * @param callable(): void $callback
     */
    private function withRun(callable $callback): void
    {
        $run = new Run([]);

        $this->dispatch(new RunStarting($run), RunStarting::NAME);

        $callback();

        $this->dispatch(new RunFinished($run), RunFinished::NAME);
    }

    private function assertStatWasLogged(string $stat, int|string $expectedValue): void
    {
        $defaults = [
            'duration' => '00:00:00',
            'requests.sent' => 0,
            'requests.dropped' => 0,
            'items.scraped' => 0,
            'items.dropped' => 0,
        ];

        self::assertTrue(
            $this->logger->messageWasLogged(
                'info',
                'Run statistics',
                \array_merge($defaults, [$stat => $expectedValue]),
            ),
        );
    }
}
