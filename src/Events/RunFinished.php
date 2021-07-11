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

namespace Sassnowski\Roach\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class RunFinished extends Event
{
    public const NAME = 'run.finished';

    private array $stats = [];

    public function addStats(array $stats): void
    {
        $this->stats = $stats;
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
