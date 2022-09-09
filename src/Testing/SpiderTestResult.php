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

namespace RoachPHP\Testing;

use PHPUnit\Framework\Assert;
use RoachPHP\Http\Request;
use RoachPHP\ItemPipeline\AbstractItem;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Spider\ParseResult;

final class SpiderTestResult
{
    /**
     * @var array<int, ItemInterface|Request>
     */
    private array $results;

    /**
     * @var array<class-string<ItemInterface>, array<int, array<array-key, mixed>>>
     */
    private array $items;

    /**
     * @var array<int, Request>
     */
    private array $requests;

    /**
     * @param array<int, ParseResult> $results
     */
    public function __construct(array $results)
    {
        $this->results = \array_map(
            static fn (ParseResult $result): ItemInterface|Request => $result->value(),
            $results,
        );

        $this->items = $this->extractScrapedItems($results);

        $this->requests = \array_filter(
            $this->results,
            static fn (ItemInterface|Request $result): bool => $result instanceof Request,
        );
    }

    /**
     * @return array<int, ItemInterface|Request>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Asserts that the provided items were scraped by the spider.
     *
     * @param array<string, mixed> ...$items
     */
    public function assertItemsScraped(array ...$items): self
    {
        $scrapedItems = $this->getAllScrapedItems();
        Assert::assertNotEmpty($scrapedItems, 'No items were scraped');

        foreach ($items as $item) {
            Assert::assertContainsEquals(
                $item,
                $scrapedItems,
                \sprintf(
                    "Expected item was not scraped:\n\n%s",
                    \json_encode($item, \JSON_PRETTY_PRINT),
                ),
            );
        }

        return $this;
    }

    /**
     * @param class-string<AbstractItem> $itemClass
     * @param array<array-key, mixed>    ...$values
     */
    public function assertCustomItemsScraped(string $itemClass, array ...$values): self
    {
        $scrapedItems = $this->items[$itemClass] ?? [];

        Assert::assertNotEmpty($scrapedItems, "No items of type {$itemClass} were scraped");

        if (\count($values) === 0) {
            return $this;
        }

        foreach ($values as $value) {
            Assert::assertContainsEquals(
                $value,
                $scrapedItems,
                \sprintf(
                    "Expected custom item %s was not scraped:\n\n%s",
                    $itemClass,
                    \json_encode($value, \JSON_PRETTY_PRINT),
                ),
            );
        }

        return $this;
    }

    /**
     * @param null|array<string, mixed> $meta
     */
    public function assertRequestDispatched(
        string $url,
        string $method = 'GET',
        ?array $meta = null,
    ): self {
        Assert::assertNotEmpty($this->requests, 'No requests were dispatched');

        $method = \mb_strtoupper($method);
        $matchingRequest = \array_filter(
            $this->requests,
            static fn (Request $request): bool => $request->getUri() === $url,
        );

        Assert::assertNotEmpty(
            $matchingRequest,
            \sprintf('No matching request to URL %s was dispatched', $url),
        );

        $matchingRequest = \array_filter(
            $matchingRequest,
            static fn (Request $request): bool => \mb_strtoupper($request->getPsrRequest()->getMethod()) === $method,
        );

        Assert::assertNotEmpty(
            $matchingRequest,
            \sprintf('Got matching request for URL "%s" but with wrong method', $url),
        );

        if (null === $meta) {
            return $this;
        }

        $matchingRequest = \array_filter(
            $matchingRequest,
            static function (Request $request) use ($meta): bool {
                /** @psalm-suppress MixedAssignment */
                foreach ($meta as $key => $value) {
                    if ($request->getMeta($key) !== $value) {
                        return false;
                    }
                }

                return true;
            },
        );

        Assert::assertNotEmpty(
            $matchingRequest,
            \sprintf('Got matching request for URL "%s" but with wrong context', $url),
        );

        return $this;
    }

    /**
     * @param null|array<string, mixed> $meta
     */
    public function assertRequestNotDispatched(string $url, string $method = 'GET', ?array $meta = null): void
    {
        $method = \mb_strtoupper($method);

        $matchingRequests = \array_filter(
            $this->requests,
            static function (Request $request) use ($url, $method): bool {
                return $request->getUri() === $url
                    && \mb_strtoupper($request->getPsrRequest()->getMethod()) === $method;
            },
        );

        if (null !== $meta) {
            $matchingRequests = \array_filter(
                $matchingRequests,
                static function (Request $request) use ($meta) {
                    /** @psalm-suppress MixedAssignment */
                    foreach ($meta as $key => $value) {
                        if ($request->getMeta($key) !== $value) {
                            return false;
                        }
                    }

                    return true;
                },
            );
        }

        Assert::assertEmpty(
            $matchingRequests,
            "Got unexpected request to url \"{$url}\"",
        );
    }

    public function assertNoRequestsDispatched(): self
    {
        Assert::assertEmpty($this->requests, 'Unexpected requests were dispatched');

        return $this;
    }

    public function assertNoItemsScraped(): self
    {
        Assert::assertEmpty($this->getAllScrapedItems(), 'Unexpected items were scraped');

        return $this;
    }

    /**
     * @param array<int, ParseResult> $results
     *
     * @return array<class-string<ItemInterface>, array<int, array<array-key, mixed>>>
     */
    private function extractScrapedItems(array $results): array
    {
        $items = [];

        foreach ($results as $result) {
            $value = $result->value();

            if ($value instanceof ItemInterface) {
                $items[$value::class][] = $value->all();
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    private function getAllScrapedItems(): array
    {
        $items = [];

        foreach ($this->items as $scrapedItems) {
            foreach ($scrapedItems as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
