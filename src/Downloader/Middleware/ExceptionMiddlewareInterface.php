<?php

declare(strict_types=1);

namespace RoachPHP\Downloader\Middleware;

use Exception;
use RoachPHP\Http\Request;
use RoachPHP\Support\ConfigurableInterface;

interface ExceptionMiddlewareInterface extends ConfigurableInterface
{
    public function handleException(Exception $exception, Request $request): ?Request;
}
