<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\Fixtures;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\Middleware\RequestMiddlewareInterface;
use RoachPHP\Support\Configurable;

final class RequestSpiderMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    public function handleRequest(Request $request, Response $response): Request
    {
        return $request;
    }

    private function defaultOptions(): array
    {
        return [
            '::option-key::' => '::default-option-value::',
        ];
    }
}
