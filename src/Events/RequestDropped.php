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

use RoachPHP\Http\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class RequestDropped extends Event
{
    public const NAME = 'request.dropped';

    public function __construct(public Request $request)
    {
    }
}
