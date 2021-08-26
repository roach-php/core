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

namespace RoachPHP\Spider\Configuration;

use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\ResponseProcessing\MiddlewareInterface;
use RoachPHP\Extensions\Extension;
use RoachPHP\Spider\ConfigurationLoaderStrategy;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArrayLoader implements ConfigurationLoaderStrategy
{
    /**
     * @var array{
     *             startUrls: string[],
     *             downloaderMiddleware: class-string<DownloaderMiddlewareInterface>[],
     *             spiderMiddleware: class-string<MiddlewareInterface>[],
     *             itemProcessors: class-string<ItemProcessorInterface>[],
     *             extensions: class-string<Extension>[],
     *             concurrency: int,
     *             requestDelay: int
     *             }
     */
    private array $config;

    public function __construct(array $configuration)
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'startUrls' => [],
            'downloaderMiddleware' => [],
            'itemProcessors' => [],
            'spiderMiddleware' => [],
            'extensions' => [],
            'concurrency' => 5,
            'requestDelay' => 0,
        ]);

        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->config = $resolver->resolve($configuration);
    }

    public function load(): Configuration
    {
        return new Configuration(
            $this->config['startUrls'],
            $this->config['downloaderMiddleware'],
            $this->config['itemProcessors'],
            $this->config['spiderMiddleware'],
            $this->config['extensions'],
            $this->config['concurrency'],
            $this->config['requestDelay'],
        );
    }
}
