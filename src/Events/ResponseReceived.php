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

use GuzzleHttp\Psr7\Response;
use Sassnowski\Roach\Spider\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class ResponseReceived extends Event
{
    public const NAME = 'response.received';

    public function __construct(private Response $response, private Request $request)
    {
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
