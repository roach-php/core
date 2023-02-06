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

namespace RoachPHP\Tests\Spider;

use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Request;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\ParseResult;

/**
 * @internal
 */
final class ParseResultTest extends TestCase
{
    public function testPassesRequestToCallbackIfResultIsRequest(): void
    {
        $result = ParseResult::request('GET', '::url::', static fn () => null);

        $result->apply(
            static fn (Request $request) => self::assertEquals('::url::', (string) $request->getUri()),
            static fn () => self::fail('Should not have been called'),
        );
    }

    public function testPassesItemToCallbackIfResultIsItem(): void
    {
        $result = ParseResult::item(['::key::' => '::value::']);

        $result->apply(
            static fn () => self::fail('Should not have been called'),
            static fn (ItemInterface $item) => self::assertSame('::value::', $item->get('::key::')),
        );
    }
}
