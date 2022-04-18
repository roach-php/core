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

namespace RoachPHP\Core;

use PHPUnit\Framework\Assert;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Spider\SpiderInterface;

final class FakeRunner implements RunnerInterface
{
    /**
     * @var array<class-string<SpiderInterface>, array<int, array{overrides: Overrides|null, context: array}>>
     */
    private array $runs = [];

    public function startSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): void
    {
        $this->recordRun($spiderClass, $overrides, $context);
    }

    public function collectSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): array
    {
        $this->recordRun($spiderClass, $overrides, $context);

        return [];
    }

    /**
     * @param class-string<SpiderInterface> $spider
     * @psalm-param (callable(Overrides|null, array): bool)|null $callback
     */
    public function assertRunWasStarted(string $spider, ?callable $callback = null): void
    {
        Assert::assertArrayHasKey(
            $spider,
            $this->runs,
            "Expected run for spider {$spider} to exist but no runs were started instead.",
        );

        if ($callback !== null) {
            foreach ($this->runs[$spider] as $run) {
                if ($callback($run['overrides'], $run['context'])) {
                    return;
                }
            }

            Assert::fail("Found run for spider $spider, but passed callback returned false");
        }
    }

    /**
     * @param class-string<SpiderInterface> $spider
     */
    public function assertRunWasNotStarted(string $spider): void
    {
        Assert::assertArrayNotHasKey(
            $spider,
            $this->runs,
            "Unexpected run for spider $spider was started",
        );
    }

    /**
     * @param class-string<SpiderInterface> $spiderClass
     */
    private function recordRun(string $spiderClass, ?Overrides $overrides = null, array $context = []): void
    {
        $this->runs[$spiderClass][] = [
            'overrides' => $overrides,
            'context' => $context,
        ];
    }
}
