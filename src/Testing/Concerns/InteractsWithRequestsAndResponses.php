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

namespace RoachPHP\Testing\Concerns;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;

/**
 * @phpstan-ignore trait.unused
 */
trait InteractsWithRequestsAndResponses
{
    private static function makeRequest(
        string $url = 'http://example.com',
        ?\Closure $callback = null,
    ): Request {
        $callback ??= static function (): \Generator {
            yield from [];
        };

        return new Request('GET', $url, $callback);
    }

    private static function makeResponse(
        ?Request $request = null,
        int $status = 200,
        ?string $body = null,
        array $headers = [],
    ): Response {
        return new Response(
            new \GuzzleHttp\Psr7\Response($status, $headers, $body),
            $request ?: static::makeRequest(),
        );
    }
}
