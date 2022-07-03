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

namespace RoachPHP\Tests\Testing;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Testing\SpiderTester;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
final class SpiderTesterTest extends TestCase
{
    public function testReturnScrapedItemsFromHTMLString(): void
    {
        $spider = $this->makeSpider(static function (Response $response): Generator {
            $items = $response->filter('.item')
                ->each(static fn (Crawler $c): string => $c->text(''));

            foreach ($items as $item) {
                yield ParseResult::item(['text' => $item]);
            }
        });
        $html = <<<'HTML'
<div>
    <p class="item">Text 1</p>
    <p class="item">Text 2</p>
</div>
HTML;
        $spiderTester = new SpiderTester($spider);

        $result = $spiderTester
            ->parseHTMLString($html)
            ->getResults();

        self::assertContainsEquals(new Item(['text' => 'Text 1']), $result);
        self::assertContainsEquals(new Item(['text' => 'Text 2']), $result);
    }

    public function testReturnScrapedItemsFromHTMLFile(): void
    {
        $spider = $this->makeSpider(static function (Response $response): Generator {
            $items = $response->filter('.item')
                ->each(static fn (Crawler $c): string => $c->text(''));

            foreach ($items as $item) {
                yield ParseResult::item(['text' => $item]);
            }
        });
        $spiderTester = new SpiderTester($spider);

        $result = $spiderTester
            ->parseHTMLFile(__DIR__ . '/../Fixtures/test1.html')
            ->getResults();

        self::assertContainsEquals(new Item(['text' => 'Text 3']), $result);
        self::assertContainsEquals(new Item(['text' => 'Text 4']), $result);
    }

    public function testThrowExceptionIfHTMLFileDoesNotExist(): void
    {
        $spiderTester = new SpiderTester($this->makeSpider());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File does-not-exist.html does not exist');

        $spiderTester
            ->parseHTMLFile('does-not-exist.html')
            ->getResults();
    }

    public function testAddRequestMetaData(): void
    {
        $spider = $this->makeSpider(static function (Response $response): Generator {
            yield ParseResult::item([
                '::key-1::' => $response->getRequest()->getMeta('::key-1::'),
                '::key-2::' => $response->getRequest()->getMeta('::key-2::'),
            ]);
        });
        $spiderTester = new SpiderTester($spider);

        $results = $spiderTester
            ->withRequestMeta([
                '::key-1::' => '::value-1::',
                '::key-2::' => '::value-2::',
            ])
            ->parseHTMLString('')
            ->getResults();

        self::assertContainsEquals(
            new Item([
                '::key-1::' => '::value-1::',
                '::key-2::' => '::value-2::',
            ]),
            $results,
        );
    }

    public function testUseDifferentParseMethod(): void
    {
        $spider = new class() extends BasicSpider {
            public function parse(Response $response): Generator
            {
                yield ParseResult::item(['::key-1::' => '::value-1::']);
            }

            public function customParseMethod(Response $response): Generator
            {
                yield from $this->parse($response);

                yield ParseResult::item(['::key-2::' => '::value-2::']);
            }
        };
        $spiderTester = new SpiderTester($spider);

        $stringResults = $spiderTester
            ->parseHTMLString('', 'customParseMethod')
            ->getResults();
        $fileResults = $spiderTester
            ->parseHTMLFile(__DIR__ . '/../Fixtures/test1.html', 'customParseMethod')
            ->getResults();

        $expected = [
            new Item(['::key-1::' => '::value-1::']),
            new Item(['::key-2::' => '::value-2::']),
        ];
        self::assertEquals($expected, $stringResults);
        self::assertEquals($expected, $fileResults);
    }

    public function testThrowsExceptionIfParseMethodDoesNotExist(): void
    {
        $spiderTester = new SpiderTester($this->makeSpider());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Method "customParseMethod" does not exist on spider %s',
            $this->makeSpider()::class,
        ));

        $spiderTester->parseHTMLString('', 'customParseMethod');
    }

    /**
     * @param null|Closure(Response): Generator<int, ParseResult> $parseCallback
     */
    private function makeSpider(?Closure $parseCallback = null): BasicSpider
    {
        $spider = new class() extends BasicSpider {
            public static ?Closure $parseCallback = null;

            public function parse(Response $response): Generator
            {
                return (self::$parseCallback)($response);
            }
        };

        $spider::$parseCallback = $parseCallback;

        return $spider;
    }
}
