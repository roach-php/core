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

namespace RoachPHP\Tests\Spider\Configuration;

use PHPUnit\Framework\TestCase;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Spider\Configuration\ArrayLoader;
use RoachPHP\Spider\Configuration\Configuration;

/**
 * @internal
 */
final class ArrayLoaderTest extends TestCase
{
    public function testLoadDefaultConfiguration(): void
    {
        $loader = new ArrayLoader([]);

        $actual = $loader->load();

        $expected = new Configuration([], [], [], [], [], 5, 0);
        self::assertEquals($expected, $actual);
    }

    public function testMergePartialOptions(): void
    {
        $loader = new ArrayLoader([
            'startUrls' => ['::start-url::'],
            'extensions' => [LoggerExtension::class],
            'concurrency' => 2,
        ]);

        $actual = $loader->load();

        $expected = new Configuration(['::start-url::'], [], [], [], [LoggerExtension::class], 2, 0);
        self::assertEquals($expected, $actual);
    }

    public function testMergeAllOptions(): void
    {
        $loader = new ArrayLoader([
            'startUrls' => ['::start-url::'],
            'downloaderMiddleware' => ['::downloader-middleware::'],
            'spiderMiddleware' => ['::spider-middleware::'],
            'itemProcessors' => ['::item-processor::'],
            'extensions' => [LoggerExtension::class],
            'concurrency' => 2,
            'requestDelay' => 2,
        ]);

        $actual = $loader->load();

        $expected = new Configuration(
            ['::start-url::'],
            ['::downloader-middleware::'], // @phpstan-ignore argument.type
            ['::item-processor::'], // @phpstan-ignore argument.type
            ['::spider-middleware::'], // @phpstan-ignore argument.type
            [LoggerExtension::class],
            2,
            2,
        );
        self::assertEquals($expected, $actual);
    }
}
