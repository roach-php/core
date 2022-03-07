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

namespace RoachPHP\Tests\Spider\Configuration;

use Generator;
use PHPUnit\Framework\TestCase;
use RoachPHP\Spider\Configuration\Configuration;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Tests\Fixtures\Extension;
use RoachPHP\Tests\Fixtures\ItemProcessor;
use RoachPHP\Tests\Fixtures\RequestDownloaderMiddleware;
use RoachPHP\Tests\Fixtures\RequestSpiderMiddleware;
use RoachPHP\Tests\Fixtures\ResponseDownloaderMiddleware;
use RoachPHP\Tests\Fixtures\ResponseSpiderMiddleware;

/**
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider overridesProvider
     */
    public function testMergeWithOverrides(array $overrides, callable $verifyConfig): void
    {
        $originalConfig = $this->makeConfiguration([
            'startUrls' => ['::original-url::'],
            'spiderMiddleware' => [
                RequestSpiderMiddleware::class,
            ],
            'downloaderMiddleware' => [
                RequestDownloaderMiddleware::class,
            ],
            'itemProcessors' => [
                ItemProcessor::class,
            ],
            'extensions' => [
                Extension::class,
            ],
            'requestDelay' => 1,
            'concurrency' => 1,
        ]);

        $overrideConfig = $originalConfig->withOverrides(new Overrides(...$overrides));

        $verifyConfig($overrideConfig);
    }

    public function overridesProvider(): Generator
    {
        yield from [
            'override startUrls' => [
                ['startUrls' => ['::override-url::']],
                static function (Configuration $config): void {
                    self::assertEquals(['::override-url::'], $config->startUrls);
                },
            ],

            'override spiderMiddleware' => [
                ['spiderMiddleware' => [ResponseSpiderMiddleware::class]],
                static function (Configuration $config): void {
                    self::assertEquals([ResponseSpiderMiddleware::class], $config->spiderMiddleware);
                },
            ],

            'override downloaderMiddleware' => [
                ['downloaderMiddleware' => [ResponseDownloaderMiddleware::class]],
                static function (Configuration $config): void {
                    self::assertEquals([ResponseDownloaderMiddleware::class], $config->downloaderMiddleware);
                },
            ],

            'override itemProcessors' => [
                ['itemProcessors' => []],
                static function (Configuration $config): void {
                    self::assertEmpty($config->itemProcessors);
                },
            ],

            'override extensions' => [
                ['extensions' => []],
                static function (Configuration $config): void {
                    self::assertEmpty($config->extensions);
                },
            ],

            'override concurrency' => [
                ['concurrency' => 10],
                static function (Configuration $config): void {
                    self::assertSame(10, $config->concurrency);
                },
            ],

            'override requestDelay' => [
                ['requestDelay' => 11],
                static function (Configuration $config): void {
                    self::assertSame(11, $config->requestDelay);
                },
            ],
        ];
    }

    private function makeConfiguration(array $values): Configuration
    {
        $defaults = [
            'startUrls' => [],
            'spiderMiddleware' => [],
            'downloaderMiddleware' => [],
            'itemProcessors' => [],
            'extensions' => [],
            'concurrency' => 1,
            'requestDelay' => 1,
        ];

        return new Configuration(...\array_merge($defaults, $values));
    }
}
