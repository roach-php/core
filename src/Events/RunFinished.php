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

namespace RoachPHP\Events;

use RoachPHP\Core\Run;
use Symfony\Contracts\EventDispatcher\Event;

final class RunFinished extends Event
{
    public const NAME = 'run.finished';

    public function __construct(private Run $run)
    {
    }

    public function getRun(): Run
    {
        return $this->run;
    }
}
