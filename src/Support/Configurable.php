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

namespace RoachPHP\Support;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait Configurable
{
    private bool $optionsResolved = false;

    private array $resolvedOptions = [];

    final public function configure(array $options): void
    {
        if ($this->optionsResolved) {
            return;
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults($this->defaultOptions());

        $this->resolvedOptions = $resolver->resolve($options);
        $this->optionsResolved = true;
    }

    public function option(string $key): mixed
    {
        if (!$this->optionsResolved) {
            $this->configure([]);
        }

        return $this->resolvedOptions[$key] ?? null;
    }

    private function defaultOptions(): array
    {
        return [];
    }
}
