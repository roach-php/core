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

namespace Sassnowski\Roach\Queue;

use Sassnowski\Roach\Http\Request;

interface RequestQueue
{
    public function enqueue(Request $request): void;

    public function dequeue(): array;

    public function empty(): bool;

    public function count(): int;
}
