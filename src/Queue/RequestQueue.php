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

use Sassnowski\Roach\Spider\Request;

interface RequestQueue
{
    public function enqueue(Request $request): void;

    public function dequeue(int $n = 1): array;

    public function count(): int;
}
