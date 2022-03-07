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

namespace RoachPHP\Events;

use RoachPHP\Http\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class RequestScheduling extends Event
{
    public const NAME = 'request.scheduling';

    public function __construct(public Request $request)
    {
    }
}
