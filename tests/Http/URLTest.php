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

namespace RoachPHP\Tests\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\MalformedUriException;
use RoachPHP\Http\URL;

/**
 * @internal
 */
final class URLTest extends TestCase
{
    public function testParseAURLFromAString(): void
    {
        $url = URL::parse(
            'https://username:password@sub.example.com:9000/foo/bar#anchor',
        );

        self::assertSame('https', $url->scheme);
        self::assertSame('username', $url->username);
        self::assertSame('password', $url->password);
        self::assertSame(9000, $url->port);
        self::assertSame('sub.example.com', $url->host);
        self::assertSame('/foo/bar', $url->path);
        self::assertSame('anchor', $url->fragment);
    }

    public function testReturnNullIfPartIsNotPresentOnURL(): void
    {
        $url = URL::parse('http://example.com');

        self::assertNull($url->port);
        self::assertNull($url->path);
        self::assertNull($url->username);
        self::assertNull($url->password);
        self::assertNull($url->fragment);
    }

    public function testItParsesTheQueryParametersOfTheURLIfPresent(): void
    {
        $url = URL::parse('http://example.com?foo=bar&baz=qux');

        self::assertTrue($url->query->equals('foo=bar&baz=qux'));
    }

    public function testReturnsAnEmptyQueryIfNoQueryIsPresentOnURL(): void
    {
        $url = URL::parse('http://example.com');

        self::assertTrue($url->query->isEmpty());
    }

    public function testThrowExceptionForMalformedURL(): void
    {
        $this->expectException(MalformedUriException::class);

        URL::parse('http:///example.com');
    }

    public function testIsEqualIfURLsAreIdentical(): void
    {
        $url1 = URL::parse(
            'https://username:password@sub.example.com:9000/foo/bar#anchor',
        );
        $url2 = URL::parse(
            'https://username:password@sub.example.com:9000/foo/bar#anchor',
        );

        self::assertTrue($url1->equals($url2));
    }

    #[DataProvider('differentUrlsProvider')]
    public function testIsNotEqualIfURLsAreDifferent(string $url1, string $url2): void
    {
        $url1 = URL::parse($url1);
        $url2 = URL::parse($url2);

        self::assertFalse($url1->equals($url2));
    }

    public static function differentUrlsProvider(): array
    {
        return [
            'scheme' => [
                'https://username:password@sub.example.com:9000/foo/bar#anchor',
                'http://username:password@sub.example.com:9000/foo/bar#anchor',
            ],
            'host' => [
                'http://username:password@example.com:9000/foo/bar#anchor',
                'http://username:password@sub.example.com:9000/foo/bar#anchor',
            ],
            'port' => [
                'http://username:password@sub.example.com:8000/foo/bar#anchor',
                'http://username:password@sub.example.com:9000/foo/bar#anchor',
            ],
            'username' => [
                'http://username1:password@sub.example.com:9000/foo/bar#anchor',
                'http://username2:password@sub.example.com:9000/foo/bar#anchor',
            ],
            'password' => [
                'http://username:password1@sub.example.com:9000/foo/bar#anchor',
                'http://username:password2@sub.example.com:9000/foo/bar#anchor',
            ],
            'path' => [
                'http://username:password@sub.example.com:9000/foo#anchor',
                'http://username:password@sub.example.com:9000/foo/bar#anchor',
            ],
            'fragment' => [
                'http://username:password@sub.example.com:9000/foo/bar#anchor1',
                'http://username:password@sub.example.com:9000/foo/bar#anchor2',
            ],
            'query' => [
                'http://username:password@sub.example.com:9000/foo/bar?foo=bar',
                'http://username:password@sub.example.com:9000/foo/bar?foo=baz',
            ],
        ];
    }

    public function testCompareEqualityWithStrings(): void
    {
        $url = URL::parse(
            'https://username:password@sub.example.com:9000/foo/bar#anchor',
        );

        self::assertTrue(
            $url->equals('https://username:password@sub.example.com:9000/foo/bar#anchor'),
        );
        self::assertFalse(
            $url->equals('https://username:password@example.com:9000/foo/bar#anchor'),
        );
    }

    public function testEqualityCheckIgnoresOrderOfQueryParameters(): void
    {
        $url = URL::parse('https://example.com?foo=bar&baz=qux');

        self::assertTrue($url->equals('https://example.com?baz=qux&foo=bar'));
    }

    #[DataProvider('urlProvider')]
    public function testCanBeTurnedIntoAString(string $urlString): void
    {
        $url = URL::parse($urlString);

        self::assertSame($urlString, $url->toString());
    }

    public static function urlProvider(): array
    {
        return [
            ['https://username:password@sub.example.com:9000/foo/bar?foo=bar#anchor'],
            ['https://sub.example.com:9000/foo/bar?foo=bar#anchor'],
            ['https://example.com:9000/foo/bar'],
            ['http://example.com:9000'],
            ['https://example.com:9000?foo=bar'],
        ];
    }
}
