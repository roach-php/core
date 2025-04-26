<?php

declare(strict_types=1);

namespace RoachPHP\Downloader\Middleware;

use RoachPHP\Http\RequestException;
use RoachPHP\Support\ConfigurableInterface;

interface ExceptionMiddlewareInterface extends ConfigurableInterface
{
    public function handleException(RequestException $requestException): RequestException;
}
