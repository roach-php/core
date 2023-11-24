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

namespace RoachPHP\Tests\Shell\Resolver;

use PHPUnit\Framework\TestCase;
use RoachPHP\Shell\Resolver\DefaultNamespaceResolverDecorator;
use RoachPHP\Shell\Resolver\FakeNamespaceResolver;
use RoachPHP\Tests\Fixtures\TestSpider;

/**
 * @internal
 */
final class DefaultNamespaceResolverDecoratorTest extends TestCase
{
    public function testPassInputThroughUnchangedIfItAlreadyPointsToExistingClass(): void
    {
        $result = $this->getResolver('::different-default-namespace::')->resolveSpiderNamespace(TestSpider::class);

        self::assertSame(TestSpider::class, $result);
    }

    /**
     * @dataProvider prependNamespaceProvider
     */
    public function testPrependsDefaultNamespaceIfPassedClassDoesNotExist(string $spiderName): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace($spiderName);

        self::assertSame('RoachPHP\Tests\Fixtures\\' . $spiderName, $result);
    }

    public static function prependNamespaceProvider(): iterable
    {
        yield from [
            'only class name' => [
                'TestSpider',
            ],

            'relative namespace' => [
                'Derp\TestSpider',
            ],
        ];
    }

    /**
     * @dataProvider defaultNamespaceProvider
     */
    public function testNormalizesDefaultNamespace(string $nonNormalizedNamespace): void
    {
        $result = $this->getResolver($nonNormalizedNamespace)->resolveSpiderNamespace('TestSpider');

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    public static function defaultNamespaceProvider(): iterable
    {
        yield from [
            'leading backslashes' => [
                '\RoachPHP\Tests\Fixtures',
            ],

            'trailing backslashes' => [
                'RoachPHP\Tests\Fixtures\\',
            ],

            'trailing spaces' => [
                'RoachPHP\Tests\Fixtures ',
            ],

            'leading spaces' => [
                ' RoachPHP\Tests\Fixtures',
            ],
        ];
    }

    /**
     * @dataProvider spiderNameProvider
     */
    public function testNormalizesProvidedSpiderName(string $nonNormalizedSpiderName): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace($nonNormalizedSpiderName);

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    public static function spiderNameProvider(): iterable
    {
        yield from [
            'leading spaces' => [
                ' TestSpider',
            ],

            'trailing spaces' => [
                'TestSpider ',
            ],
        ];
    }

    public function testTreatsLeadingBackslashesAsAbsolutePathAndReturnsItAsIs(): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace('\Test\Spider');

        self::assertSame('\Test\Spider', $result);
    }

    public function testDoesNotPrependDefaultNamespaceIfInputAlreadyStartsWithIt(): void
    {
        $result = $this->getResolver('::default-namespace::')->resolveSpiderNamespace('::default-namespace::\Spider');

        self::assertSame('::default-namespace::\Spider', $result);
    }

    private function getResolver(string $defaultNamespace = 'RoachPHP\Tests\Fixtures'): DefaultNamespaceResolverDecorator
    {
        return new DefaultNamespaceResolverDecorator(
            new FakeNamespaceResolver(),
            $defaultNamespace,
        );
    }
}
