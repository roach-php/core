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

namespace Sassnowski\Roach\Testing;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\Assert;
use Sassnowski\Roach\Http\Middleware\HandlerInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

final class FakeHandler implements HandlerInterface
{
    /**
     * @var Request[]
     */
    private array $calls = [];

    public function __invoke(Request $request): PromiseInterface
    {
        $this->calls[] = $request;

        $promise = new Promise(static function () use (&$promise, $request): void {
            $promise->resolve(new Response(new \GuzzleHttp\Psr7\Response(), $request));
        });

        return $promise;
    }

    public function assertWasCalledWith(Request $expected): void
    {
        foreach ($this->calls as $request) {
            if ($request === $expected) {
                Assert::assertTrue(true);

                return;
            }
        }

        Assert::fail('Handler was not called with expected request');
    }
}
