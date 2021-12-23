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

namespace RoachPHP\Spider\Middleware;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

final class MaximumCrawlDepthMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    public function handleRequest(Request $request, Response $response): Request
    {
        $currentDepth = (int) $response->getRequest()->getMeta('depth', 1);
        $newDepth = $currentDepth + 1;

        if ($this->option('maxCrawlDepth') < $newDepth) {
            return $request->drop('Maximum crawl depth reached');
        }

        return $request->withMeta('depth', $currentDepth + 1);
    }

    private function defaultOptions(): array
    {
        return [
            'maxCrawlDepth' => 10,
        ];
    }
}
