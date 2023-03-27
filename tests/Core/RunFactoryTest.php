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

namespace RoachPHP\Tests\Core;

use Generator;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use RoachPHP\Core\Run;
use RoachPHP\Core\RunFactory;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Spider\SpiderMiddlewareInterface;
use RoachPHP\Tests\Fixtures\Extension;
use RoachPHP\Tests\Fixtures\ItemProcessor;
use RoachPHP\Tests\Fixtures\ItemSpiderMiddleware;
use RoachPHP\Tests\Fixtures\RequestDownloaderMiddleware;
use RoachPHP\Tests\Fixtures\RequestSpiderMiddleware;
use RoachPHP\Tests\Fixtures\ResponseDownloaderMiddleware;
use RoachPHP\Tests\Fixtures\ResponseSpiderMiddleware;

/**
 * @internal
 */
final class RunFactoryTest extends TestCase
{
    private RunFactory $factory;

    private Container $container;

    protected function setUp(): void
    {
        $this->container = (new Container())->delegate(new ReflectionContainer());
        $this->factory = new RunFactory($this->container);
    }

    public function testGetInitialRequestsFromSpider(): void
    {
        $spider = $this->createSpider(startUrls: ['::url-1::', '::url-2::']);

        $run = $this->factory->fromSpider($spider);

        self::assertCount(2, $run->startRequests);
        self::assertSame('::url-1::', $run->startRequests[0]->getUri());
        self::assertSame('::url-2::', $run->startRequests[1]->getUri());
    }

    public function testWrapDownloaderMiddlewareInAdapater(): void
    {
        $spider = $this->createSpider(downloaderMiddleware: [
            RequestDownloaderMiddleware::class,
            ResponseDownloaderMiddleware::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertCount(2, $run->downloaderMiddleware);
        self::assertInstanceOf(DownloaderMiddlewareInterface::class, $run->downloaderMiddleware[0]);
        self::assertInstanceOf(DownloaderMiddlewareInterface::class, $run->downloaderMiddleware[1]);
    }

    public function testConfigureDownloaderMiddlewareWithDefaults(): void
    {
        $spider = $this->createSpider(downloaderMiddleware: [
            RequestDownloaderMiddleware::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::default-option-value::',
            $run->downloaderMiddleware[0]->getMiddleware()->option('::option-key::'),
        );
    }

    public function testConfigureDownloaderMiddlewareWithOverrides(): void
    {
        $spider = $this->createSpider(downloaderMiddleware: [
            [RequestDownloaderMiddleware::class, ['::option-key::' => '::override-value::']],
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::override-value::',
            $run->downloaderMiddleware[0]->getMiddleware()->option('::option-key::'),
        );
    }

    public function testWrapSpiderMiddlewareInAdapter(): void
    {
        $spider = $this->createSpider(spiderMiddleware: [
            RequestSpiderMiddleware::class,
            ResponseSpiderMiddleware::class,
            ItemSpiderMiddleware::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertCount(3, $run->responseMiddleware);
        self::assertInstanceOf(SpiderMiddlewareInterface::class, $run->responseMiddleware[0]);
        self::assertInstanceOf(SpiderMiddlewareInterface::class, $run->responseMiddleware[1]);
        self::assertInstanceOf(SpiderMiddlewareInterface::class, $run->responseMiddleware[2]);
    }

    public function testConfigureSpiderMiddlewareWithDefaults(): void
    {
        $spider = $this->createSpider(spiderMiddleware: [
            RequestSpiderMiddleware::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::default-option-value::',
            $run->responseMiddleware[0]->getMiddleware()->option('::option-key::'),
        );
    }

    public function testConfigureSpiderMiddlewareWithOverrides(): void
    {
        $spider = $this->createSpider(spiderMiddleware: [
            [RequestSpiderMiddleware::class, ['::option-key::' => '::override-value::']],
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::override-value::',
            $run->responseMiddleware[0]->getMiddleware()->option('::option-key::'),
        );
    }

    public function testConfigureItemProcessorsWithDefaults(): void
    {
        $spider = $this->createSpider(itemProcessors: [
            ItemProcessor::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::default-option-value::',
            $run->itemProcessors[0]->option('::option-key::'),
        );
    }

    public function testConfigureItemProcessorsWithOverrides(): void
    {
        $spider = $this->createSpider(itemProcessors: [
            [ItemProcessor::class, ['::option-key::' => '::override-value::']],
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::override-value::',
            $run->itemProcessors[0]->option('::option-key::'),
        );
    }

    public function testConfigureExtensionsWithDefaults(): void
    {
        $spider = $this->createSpider(extensions: [
            Extension::class,
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::default-option-value::',
            $run->extensions[0]->option('::option-key::'),
        );
    }

    public function testConfigureExtensionsWithOverrides(): void
    {
        $spider = $this->createSpider(extensions: [
            [Extension::class, ['::option-key::' => '::override-value::']],
        ]);

        $run = $this->factory->fromSpider($spider);

        self::assertSame(
            '::override-value::',
            $run->extensions[0]->option('::option-key::'),
        );
    }

    /**
     * @dataProvider numberProvider
     */
    public function testConfigureConcurrencyWithDefault(int $concurrency): void
    {
        $spider = $this->createSpider(concurrency: $concurrency);

        $run = $this->factory->fromSpider($spider);

        self::assertSame($concurrency, $run->concurrency);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testConfigureRequestDelay(int $requestDelay): void
    {
        $spider = $this->createSpider(requestDelay: $requestDelay);

        $run = $this->factory->fromSpider($spider);

        self::assertSame($requestDelay, $run->requestDelay);
    }

    public function testConfigureRunNamespace(): void
    {
        $spider = $this->createSpider();

        $run = $this->factory->fromSpider($spider);

        self::assertSame($spider::class, $run->namespace);
    }

    public static function numberProvider(): Generator
    {
        yield from [
            [1],
            [2],
            [3],
            [4],
            [5],
        ];
    }

    /**
     * @dataProvider configurationOverrideProvider
     */
    public function testMergeSpiderConfigurationWithRunOverrides(array $overrides, callable $verifyRun): void
    {
        $defaults = [
            'startUrls' => [
                '::default-url::',
            ],
            'itemProcessors' => [
                ItemProcessor::class,
            ],
            'spiderMiddleware' => [
                RequestSpiderMiddleware::class,
                ResponseSpiderMiddleware::class,
                ItemSpiderMiddleware::class,
            ],
            'downloaderMiddleware' => [
                ResponseDownloaderMiddleware::class,
                RequestDownloaderMiddleware::class,
            ],
            'extensions' => [
                Extension::class,
            ],
            'requestDelay' => 5,
            'concurrency' => 2,
        ];
        $spider = $this->createSpider(...$defaults);

        $run = $this->factory->fromSpider($spider, new Overrides(...$overrides));

        $verifyRun($run);
    }

    public static function configurationOverrideProvider(): Generator
    {
        yield from [
            'override start urls' => [
                ['startUrls' => ['::override-url-1::', '::override-url-2::']],
                static function (Run $run): void {
                    self::assertCount(2, $run->startRequests);
                    self::assertSame('::override-url-1::', $run->startRequests[0]->getUri());
                    self::assertSame('::override-url-2::', $run->startRequests[1]->getUri());
                },
            ],

            'override itemProcessors' => [
                ['itemProcessors' => []],
                static function (Run $run): void {
                    self::assertEmpty($run->itemProcessors);
                },
            ],

            'override spiderMiddleware' => [
                ['spiderMiddleware' => [ItemSpiderMiddleware::class]],
                static function (Run $run): void {
                    self::assertCount(1, $run->responseMiddleware);
                    self::assertInstanceOf(
                        ItemSpiderMiddleware::class,
                        $run->responseMiddleware[0]->getMiddleware(),
                    );
                },
            ],

            'override downloaderMiddleware' => [
                ['downloaderMiddleware' => [ResponseDownloaderMiddleware::class]],
                static function (Run $run): void {
                    self::assertCount(1, $run->downloaderMiddleware);
                    self::assertInstanceOf(
                        ResponseDownloaderMiddleware::class,
                        $run->downloaderMiddleware[0]->getMiddleware(),
                    );
                },
            ],

            'override extensions' => [
                ['extensions' => []],
                static function (Run $run): void {
                    self::assertEmpty($run->extensions);
                },
            ],

            'override concurrency' => [
                ['concurrency' => 25],
                static function (Run $run): void {
                    self::assertSame(25, $run->concurrency);
                },
            ],

            'override requestDelay' => [
                ['requestDelay' => 150],
                static function (Run $run): void {
                    self::assertSame(150, $run->requestDelay);
                },
            ],
        ];
    }

    private function createSpider(
        array $startUrls = [],
        array $downloaderMiddleware = [],
        array $spiderMiddleware = [],
        array $itemProcessors = [],
        array $extensions = [],
        int $concurrency = 1,
        int $requestDelay = 2,
    ): BasicSpider {
        return new class($startUrls, $downloaderMiddleware, $spiderMiddleware, $itemProcessors, $extensions, $concurrency, $requestDelay) extends BasicSpider {
            public function __construct(
                public array $startUrls,
                public array $downloaderMiddleware,
                public array $spiderMiddleware,
                public array $itemProcessors,
                public array $extensions,
                public int $concurrency,
                public int $requestDelay,
            ) {
                parent::__construct();
            }

            public function parse(Response $response): Generator
            {
                yield from [];
            }
        };
    }
}
