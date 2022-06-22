<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\ItemPipeline;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RoachPHP\Tests\Fixtures\TestItem;
use RuntimeException;

/**
 * @internal
 */
final class AbstractItemTest extends TestCase
{
    public function testCanGetPublicProperty(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::value-1::', $item->get('foo'));
        self::assertSame('::value-2::', $item->get('bar'));
    }

    public function testReturnDefaultValueIfNoPublicPropertyExistsForName(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::default::', $item->get('baz', '::default::'));
        self::assertSame('::default::', $item->get('qux', '::default::'));
        self::assertSame('::default::', $item->get('lorem-ipsum', '::default::'));
    }

    public function testReturnDefaultValueIfPropertyIsNull(): void
    {
        $item = new TestItem(foo: '::value::', bar: null);

        self::assertSame('::default::', $item->get('bar', '::default::'));
    }

    public function testCanGetAllPublicProperties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertEquals([
            'foo' => '::value-1::',
            'bar' => '::value-2::',
        ], $item->all());
    }

    public function testCanSetPublicProperty(): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $item->set('foo', '::new-value-1::');
        $item->set('bar', '::new-value-2::');

        self::assertSame('::new-value-1::', $item->foo);
        self::assertSame('::new-value-2::', $item->bar);
    }

    /**
     * @dataProvider inaccessiblePropertiesProvider
     */
    public function testThrowsExceptionWhenTryingToSetNonPublicOrNonExistentProperty(string $property): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No public property {$property} exists on class RoachPHP\\Tests\\Fixtures\\TestItem");
        $item->set($property, '::new-value::');
    }

    /**
     * @dataProvider hasPropertyProvider
     */
    public function testHasProperty(string $property, bool $expected): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame($expected, $item->has($property));
    }

    /**
     * @dataProvider hasPropertyProvider
     */
    public function testOffsetExists(string $property, bool $expected): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame($expected, isset($item[$property]));
    }

    public function hasPropertyProvider(): Generator
    {
        yield from [
            'public property 1' => ['foo', true],
            'public property 2' => ['bar', true],
            'protected property' => ['baz', false],
            'private property' => ['qux', false],
            'non-existent property' => ['does-not-exist', false],
        ];
    }

    public function testOffsetGetCanRetrievePublicProperties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::value-1::', $item['foo']);
        self::assertSame('::value-2::', $item['bar']);
    }

    public function testOffsetGetReturnsNullForNonAccessibleProperty(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertNull($item['baz']);
        self::assertNull($item['qux']);
    }

    public function testOffsetGetReturnsNullForNonExistentProperty(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertNull($item['does-not-exist']);
    }

    public function testOffsetSetCanSetPublicProperties(): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $item['foo'] = '::new-value-1::';
        $item['bar'] = '::new-value-2::';

        self::assertSame('::new-value-1::', $item->foo);
        self::assertSame('::new-value-2::', $item->bar);
    }

    /**
     * @dataProvider inaccessiblePropertiesProvider
     */
    public function testOffsetSetThrowsExceptionWhenSettingInaccessibleOrNonExistentProperty(string $property): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No public property {$property} exists on class RoachPHP\\Tests\\Fixtures\\TestItem");

        $item[$property] = '::new-value::';
    }

    public function inaccessiblePropertiesProvider(): Generator
    {
        yield from [
            'protected property' => ['baz'],
            'private property' => ['qux'],
            'non-existent property' => ['does-not-exist'],
        ];
    }

    public function testDoesNotSupportUnsettingProperties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsetting properties is not supported for custom item classes');

        unset($item['foo']);
    }
}
