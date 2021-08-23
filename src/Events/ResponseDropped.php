<?php

namespace Sassnowski\Roach\Events;

use Sassnowski\Roach\Http\Response;
use Symfony\Contracts\EventDispatcher\Event;

class ResponseDropped extends Event
{
    public const NAME = 'response.dropped';

    public function __construct(public Response $response)
    {
    }
}