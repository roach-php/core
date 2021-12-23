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

namespace RoachPHP\Spider\Middleware;

use RoachPHP\Support\ConfigurableInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Handler implements ConfigurableInterface
{
    private OptionsResolver $resolver;

    public function __construct(protected array $options = [])
    {
        $this->resolver = new OptionsResolver();

        $this->resolver->setDefaults($options);
    }

    final public function configure(array $options): void
    {
        $this->options = $this->resolver->resolve($options);
    }
}
