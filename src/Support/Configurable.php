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

namespace RoachPHP\Support;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait Configurable
{
    private bool $optionsResolved = false;

    private array $resolvedOptions = [];

    /**
     * @param array<string, mixed> $options
     *
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    public static function withOptions(array $options): array
    {
        return [static::class, $options];
    }

    /**
     * @param array<string, mixed> $options
     */
    final public function configure(array $options): void
    {
        if ($this->optionsResolved) {
            return;
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults($this->defaultOptions());

        $this->resolvedOptions = $resolver->resolve($options);
        $this->optionsResolved = true;

        $this->onAfterConfigured();
    }

    public function option(string $key): mixed
    {
        if (!$this->optionsResolved) {
            $this->configure([]);
        }

        return $this->resolvedOptions[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultOptions(): array
    {
        return [];
    }

    /**
     * Called after the `configure` method was called on the object the first
     * time. This is a good place to perform any one-time setup that should
     * happen before the run starts.
     */
    private function onAfterConfigured(): void
    {
    }
}
