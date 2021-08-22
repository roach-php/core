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

namespace Sassnowski\Roach\Spider\Configuration;

use Sassnowski\Roach\Spider\ConfigurationLoaderStrategy;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArrayLoader implements ConfigurationLoaderStrategy
{
    private array $config;

    public function __construct(array $configuration)
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'startUrls' => [],
            'downloaderMiddleware' => [],
            'itemProcessors' => [],
            'spiderMiddleware' => [],
            'concurrency' => 5,
            'requestDelay' => 0,
        ]);

        $this->config = $resolver->resolve($configuration);
    }

    public function load(): Configuration
    {
        return new Configuration(
            $this->config['startUrls'],
            $this->config['downloaderMiddleware'],
            $this->config['itemProcessors'],
            $this->config['spiderMiddleware'],
            $this->config['concurrency'],
            $this->config['requestDelay'],
        );
    }
}
