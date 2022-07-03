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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\SpiderTestResult;
use RoachPHP\Tests\Fixtures\TestItem;
use RoachPHP\Tests\Fixtures\TestItem2;

/**
 * @internal
 */
final class SpiderTestResultTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testAssertItemsScrapedPassesForSingleScrapedItem(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key::' => '::value::']),
        ]);

        $testResult->assertItemsScraped(
            ['::key::' => '::value::'],
        );
    }

    public function testAssertItemsScrapedFailsIfNoMatchingItemWasScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key::' => '::value::']),
        ]);

        $this->expectException(AssertionFailedError::class);
        $testResult->assertItemsScraped(
            ['::different-key::' => '::different-item::'],
        );
    }

    public function testAssertItemsScrapedPassesIfAllItemsWereScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key-1::' => '::value-1::']),
            ParseResult::item(['::key-2::' => '::value-2::']),
        ]);

        $testResult->assertItemsScraped(
            ['::key-1::' => '::value-1::'],
            ['::key-2::' => '::value-2::'],
        );
    }

    public function testAssertItemsScrapedFailsIfOnlySomeOfTheItemsWereScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key-1::' => '::value-1::']),
        ]);

        $itemJSON = \json_encode(['::key-2::' => '::value-2::'], flags: \JSON_PRETTY_PRINT);
        $expectedMessage = \sprintf("Expected item was not scraped:\n\n%s", $itemJSON);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage($expectedMessage);
        $testResult->assertItemsScraped(
            ['::key-1::' => '::value-1::'],
            ['::key-2::' => '::value-2::'],
        );
    }

    public function testAssertCustomItemsScrapedPassesIfAtLeastOneCustomItemWithCorrectTypeWasScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue(new TestItem('::foo::', '::bar::')),
        ]);

        $testResult->assertCustomItemsScraped(TestItem::class);
    }

    public function testAssertCustomItemsScrapedFailsIfNoCustomItemsOfCorrectTypeWereScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue(new TestItem2()),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(\sprintf('No items of type %s were scraped', TestItem::class));
        $testResult->assertCustomItemsScraped(TestItem::class);
    }

    public function testAssertCustomItemsScrapedFailsIfNoCustomItemWithCorrectPayloadWasScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue(new TestItem('::foo::', '::bar::')),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            \sprintf(
                "Expected custom item %s was not scraped:\n\n%s",
                TestItem::class,
                \json_encode(['foo' => '::not-foo::', 'bar' => '::not-bar::'], \JSON_PRETTY_PRINT),
            ),
        );
        $testResult->assertCustomItemsScraped(
            TestItem::class,
            ['foo' => '::not-foo::', 'bar' => '::not-bar::'],
        );
    }

    public function testAssertCustomItemScrapedPassesIfCustomItemsWithCorrectPayloadWereScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue(new TestItem('::foo-1::', '::bar-1::')),
            ParseResult::fromValue(new TestItem('::foo-2::', '::bar-2::')),
        ]);

        $testResult->assertCustomItemsScraped(
            TestItem::class,
            ['foo' => '::foo-1::', 'bar' => '::bar-1::'],
            ['foo' => '::foo-2::', 'bar' => '::bar-2::'],
        );
    }

    public function testAssertRequestDispatchedPassesIfMatchingRequestWasYielded(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($this->makeRequest('::url::')),
        ]);

        $testResult->assertRequestDispatched('::url::');
    }

    public function testAssertRequestDispatchedFailsIfNoRequestWasYielded(): void
    {
        $testResult = new SpiderTestResult([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No requests were dispatched');
        $testResult->assertRequestDispatched('::url::');
    }

    public function testAssertRequestDispatchedFailsIfRequestWasSentToCorrectURLButWithWrongMethod(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($this->makeRequest('::url::')),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Got matching request for URL "::url::" but with wrong method',
        );
        $testResult->assertRequestDispatched('::url::', 'POST');
    }

    public function testAssertRequestDispatchedPassesIfRequestContextMatchesAsWell(): void
    {
        $request = $this->makeRequest('::url::');
        $request = $request->withMeta('::key::', '::value::');
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($request),
        ]);

        $testResult->assertRequestDispatched('::url::', meta: ['::key::' => '::value::']);
    }

    public function testAssertRequestDispatchedFailsIfRequestContextDoesNotMatch(): void
    {
        $request = $this->makeRequest('::url::');
        $request = $request->withMeta('::key::', '::value::');
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($request),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Got matching request for URL "::url::" but with wrong context',
        );
        $testResult->assertRequestDispatched(
            '::url::',
            meta: ['::different-key::' => '::different-value::'],
        );
    }

    public function testAssertNoRequestsDispatchedPassesIfNoRequestsWereDispatched(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key::' => '::item::']),
        ]);

        $testResult->assertNoRequestsDispatched();
    }

    public function testAssertNoRequestsDispatchedFailsIfAtLeastOneRequestWasDispatched(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($this->makeRequest()),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected requests were dispatched');
        $testResult->assertNoRequestsDispatched();
    }

    public function testAssertNoItemsScrapedPassesIfNoItemsWereScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::fromValue($this->makeRequest()),
        ]);

        $testResult->assertNoItemsScraped();
    }

    public function testAssertNoItemsScrapedFailsIfAtLeastOneItemWasScraped(): void
    {
        $testResult = new SpiderTestResult([
            ParseResult::item(['::key::' => '::value::']),
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected items were scraped');
        $testResult->assertNoItemsScraped();
    }
}
