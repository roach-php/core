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

namespace RoachPHP\Extensions;

use RoachPHP\Events\RequestScheduling;
use RoachPHP\Events\RequestSending;

final class MaxRequestExtension extends Extension
{
    private int $sentRequests = 0;

    public function __construct()
    {
        parent::__construct([
            'limit' => 10,
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestSending::NAME => ['onRequestSending', 10000],
            RequestScheduling::NAME => ['onRequestScheduling', 0],
        ];
    }

    public function onRequestSending(RequestSending $event): void
    {
        $this->dropRequestIfLimitReached($event);

        if (!$event->request->wasDropped()) {
            ++$this->sentRequests;
        }
    }

    public function onRequestScheduling(RequestScheduling $event): void
    {
        $this->dropRequestIfLimitReached($event);
    }

    private function dropRequestIfLimitReached(RequestSending|RequestScheduling $event): void
    {
        if ($this->sentRequests >= $this->options['limit']) {
            $event->request = $event->request->drop("Reached maximum request limit of {$this->options['limit']}");
        }
    }
}
