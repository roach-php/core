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

namespace RoachPHP\Tests\Shell\Resolver;

use PHPUnit\Framework\TestCase;
use RoachPHP\Shell\Resolver\FakeNamespaceResolver;
use RoachPHP\Tests\Fixtures\RequestSpiderMiddleware;
use RoachPHP\Tests\Fixtures\TestSpider;

/**
 * @internal
 */
final class FakeNamespaceResolverTest extends TestCase
{
    /**
     * @dataProvider inputStringProvider
     */
    public function testAlwaysReturnsTheOriginalString(string $input): void
    {
        $result = (new FakeNamespaceResolver())->resolveSpiderNamespace($input);

        self::assertSame($input, $result);
    }

    public static function inputStringProvider(): iterable
    {
        yield from [
            ['::string-1::'],
            [TestSpider::class],
            ['::string-2::'],
            [RequestSpiderMiddleware::class],
        ];
    }
}
