<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Pavlo Komarov
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader\Middleware;

use JetBrains\PhpStorm\ArrayShape;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\RequestScheduling;
use RoachPHP\Exception\Exception;
use RoachPHP\Http\Request;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use RoachPHP\Support\Configurable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RetryMiddleware implements ExceptionMiddlewareInterface, RequestMiddlewareInterface
{
    use Configurable;

    private RequestSchedulerInterface $requestScheduler;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(RequestSchedulerInterface $requestScheduler, EventDispatcherInterface $eventDispatcher)
    {
        $this->requestScheduler = $requestScheduler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleRequest(Request $request): Request
    {
        return $request->withMeta('max_retries', $this->option('max_retries'));
    }


    public function handleException(Exception $exception): Exception
    {
        $request = $exception->getRequest();

        $retries = $request->getMeta('retries', 0);
        if ($retries < $request->getMeta('max_retries')) {
            $request = $request->withMeta('retries', ++$retries);
            $this->eventDispatcher->dispatch(
                new RequestScheduling($request),
                RequestScheduling::NAME,
            );

            if ($request->wasDropped()) {
                $this->eventDispatcher->dispatch(
                    new RequestDropped($request),
                    RequestDropped::NAME,
                );

                return $exception;
            }

            $this->requestScheduler->schedule($request);
        }

        return $exception;
    }

    #[ArrayShape(['max_retries' => "int"])]
    private function defaultOptions(): array
    {
        return [
            'max_retries' => 5,
        ];
    }
}
