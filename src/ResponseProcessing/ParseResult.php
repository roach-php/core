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

namespace RoachPHP\ResponseProcessing;

use Closure;
use Generator;
use InvalidArgumentException;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;

final class ParseResult
{
    private function __construct(private ?Request $request, private ?ItemInterface $item)
    {
    }

    public static function fromValue(Request|ItemInterface $value): self
    {
        if ($value instanceof ItemInterface) {
            return new self(null, $value);
        }

        return new self($value, null);
    }

    public static function item(array $item): self
    {
        return new self(null, new Item($item));
    }

    public function value(): Request|ItemInterface
    {
        if (null !== $this->request) {
            return $this->request;
        }

        if (null !== $this->item) {
            return $this->item;
        }

        throw new InvalidArgumentException(
            'Neither item nor request is set. This should never happen',
        );
    }

    /**
     * @param callable(Response): Generator<ParseResult> $parseCallback
     */
    public static function request(string $url, callable $parseCallback): self
    {
        return new self(
            new Request($url, $parseCallback),
            null,
        );
    }

    /**
     * @param Closure(Request): void       $ifRequest
     * @param Closure(ItemInterface): void $ifItem
     */
    public function apply(Closure $ifRequest, Closure $ifItem): void
    {
        if (null !== $this->request) {
            $ifRequest($this->request);
        } elseif (null !== $this->item) {
            $ifItem($this->item);
        } else {
            throw new InvalidArgumentException('ParseResult with empty item and result. This should never happen');
        }
    }
}
