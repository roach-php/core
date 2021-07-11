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

namespace Sassnowski\Roach\Middleware;

use Sassnowski\Roach\Events\RequestScheduling;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RequestDeduplicationMiddleware implements EventSubscriberInterface
{
    public function __construct(private array $seenUrls = [])
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestScheduling::NAME => [
                ['onRequestScheduling', 900],
            ],
        ];
    }

    public function onRequestScheduling(RequestScheduling $event): void
    {
        $request = $event->getRequest();
        $url = (string) $request->getUri();

        if (\in_array($url, $this->seenUrls, true)) {
            $request->drop();
        } else {
            $this->seenUrls[] = $url;
        }
    }
}
