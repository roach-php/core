<?php

declare(strict_types=1);

namespace RoachPHP\Events;

use Exception;
use Symfony\Contracts\EventDispatcher\Event;

final class ExceptionReceived extends Event
{
    public const NAME = 'exception.processed';

    public function __construct(public Exception $exception)
    {
    }
}
