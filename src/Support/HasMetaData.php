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

namespace Sassnowski\Roach\Support;

trait HasMetaData
{
    private array $meta = [];

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    public function withMeta(string $key, mixed $value): static
    {
        $newThis = clone $this;
        $newThis->meta[$key] = $value;

        return $newThis;
    }
}
