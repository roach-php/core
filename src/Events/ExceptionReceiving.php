<?php

declare(strict_types=1);

namespace RoachPHP\Events;

use Exception;
use Symfony\Contracts\EventDispatcher\Event;

final class ExceptionReceiving extends Event
{
    public const NAME = 'exception.receiving';

    public function __construct(public Exception $exception)
    {
    }
}
