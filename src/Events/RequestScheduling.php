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

use Sassnowski\Roach\Spider\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class RequestScheduling extends Event
{
    public const NAME = 'request.scheduling';

    public function __construct(private Request $request)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
