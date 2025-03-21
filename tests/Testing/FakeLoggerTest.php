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

namespace RoachPHP\Tests\Testing;

use PHPUnit\Framework\TestCase;
use RoachPHP\Testing\FakeLogger;

/**
 * @group testing
 *
 * @internal
 */
final class FakeLoggerTest extends TestCase
{
    /**
     * @dataProvider logMessageProvider
     *
     * @param array<array-key, mixed> $context
     */
    public function testCheckIfSpecificMessageWasLoggedAtLevel(string $level, string $message, array $context): void
    {
        $logger = new FakeLogger();

        self::assertFalse($logger->messageWasLogged($level, $message));

        $logger->{$level}($message, $context);

        self::assertTrue($logger->messageWasLogged($level, $message));
    }

    /**
     * @dataProvider logMessageProvider
     *
     * @param array<array-key, mixed> $context
     */
    public function testCheckIfMessageWasLoggedWithContext(string $level, string $message, array $context): void
    {
        $logger = new FakeLogger();

        $logger->{$level}($message, []);
        self::assertFalse($logger->messageWasLogged($level, $message, $context));

        $logger->{$level}($message, $context);
        self::assertTrue($logger->messageWasLogged($level, $message, $context));
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: array<array-key, mixed>}>
     */
    public static function logMessageProvider(): iterable
    {
        yield from [
            'debug' => [
                'debug', '::debug-message::', ['::debug-context::'],
            ],
            'info' => [
                'info', '::info-message::', ['::info-context::'],
            ],
            'notice' => [
                'notice', '::notice-message::', ['::notice-context::'],
            ],
            'warning' => [
                'warning', '::warning-message::', ['::warning-context::'],
            ],
            'error' => [
                'error', '::error-message::', ['::error-context::'],
            ],
            'critical' => [
                'critical', '::critical-message::', ['::critical-context::'],
            ],
            'alert' => [
                'alert', '::alert-message::', ['::alert-context::'],
            ],
            'emergency' => [
                'emergency', '::emergency-message::', ['::emergency-context::'],
            ],
        ];
    }
}
