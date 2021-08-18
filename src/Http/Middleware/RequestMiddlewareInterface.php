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

namespace Sassnowski\Roach\Http\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Sassnowski\Roach\Http\Request;

interface RequestMiddlewareInterface
{
    /**
     * @throws DropRequestException thrown if the request should not be processed further
     */
    public function handle(Request $request, HandlerInterface $next): PromiseInterface;

    public function configure(array $options): void;
}
