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

trait Droppable
{
    private string $dropReason = '';

    private bool $dropped = false;

    public function drop(string $reason): static
    {
        $clone = clone $this;
        $clone->dropped = true;
        $clone->dropReason = $reason;

        return $clone;
    }

    public function wasDropped(): bool
    {
        return $this->dropped;
    }

    public function getDropReason(): string
    {
        return $this->dropReason;
    }
}
