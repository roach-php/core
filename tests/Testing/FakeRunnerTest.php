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

namespace RoachPHP\Tests\Testing;

use Generator;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Core\FakeRunner;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Tests\Fixtures\TestSpider;
use RoachPHP\Tests\Fixtures\TestSpider2;

/**
 * @internal
 */
final class FakeRunnerTest extends TestCase
{
    private FakeRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new FakeRunner();
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasStartedPassesIfAnyRunForTheGivenSpiderClassWasStarted(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    public function testAssertRunWasStartedFailsIfNoRunWasStartedAtAll(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasStartedFailsIfNoRunWasStartedForTheGivenSpider(string $method): void
    {
        $this->runner->{$method}(TestSpider2::class);

        $this->expectException(AssertionFailedError::class);
        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasStartedPassesIfTheProvidedClosureReturnsTrue(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->runner->assertRunWasStarted(TestSpider::class, static fn () => true);
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasStartedPassesIfCallbackReturnsTrueForAnyOfTheFoundRuns(string $method): void
    {
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'bar']);
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'baz']);
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'qux']);

        $this->runner->assertRunWasStarted(
            TestSpider::class,
            static fn (?Overrides $_, array $context): bool => 'qux' === $context['foo'],
        );
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasStartedFailsIfTheProvidedClosureReturnsFalse(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->expectException(AssertionFailedError::class);
        $this->runner->assertRunWasStarted(TestSpider::class, static fn () => false);
    }

    /**
     * @dataProvider  runnerMethodProvider
     */
    public function testAssertRunWasNotStartedPassesIfNoRunForTheGivenSpiderClassWasStarted(string $method): void
    {
        $this->runner->{$method}(TestSpider2::class);

        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    public function testAssertRunWasNotStartedPassesIfNoRunWasStartedAtAll(): void
    {
        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    /**
     * @dataProvider runnerMethodProvider
     */
    public function testAssertRunWasNotStartedFailsIfRunForSpiderWasStarted(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->expectException(AssertionFailedError::class);

        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    public static function runnerMethodProvider(): Generator
    {
        yield from [
            'startSpider' => ['startSpider'],
            'collectSpider' => ['collectSpider'],
        ];
    }
}
