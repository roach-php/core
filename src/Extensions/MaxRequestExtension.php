<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Extensions;

use RoachPHP\Events\RequestScheduling;
use RoachPHP\Events\RequestSending;
use RoachPHP\Support\Configurable;

final class MaxRequestExtension implements ExtensionInterface
{
    use Configurable;

    private int $sentRequests = 0;

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

    private function dropRequestIfLimitReached(RequestScheduling|RequestSending $event): void
    {
        /** @var int $limit */
        $limit = $this->option('limit');

        if ($limit <= $this->sentRequests) {
            $event->request = $event->request->drop("Reached maximum request limit of {$limit}");
        }
    }

    private function defaultOptions(): array
    {
        return [
            'limit' => 10,
        ];
    }
}
