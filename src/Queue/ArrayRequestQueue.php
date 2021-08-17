<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Queue;

use Sassnowski\Roach\Http\Request;

final class ArrayRequestQueue implements RequestQueue
{
    private array $requests = [];

    public function enqueue(Request $request): void
    {
        $this->requests[] = $request;
    }

    public function dequeue(): array
    {
        $result = $this->requests;

        $this->requests = [];

        return $result;
    }

    public function count(): int
    {
        return \count($this->requests);
    }

    public function empty(): bool
    {
        return $this->count() === 0;
    }
}
