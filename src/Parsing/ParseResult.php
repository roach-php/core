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

namespace Sassnowski\Roach\Parsing;

use Closure;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\ItemPipeline\Item;
use Sassnowski\Roach\ItemPipeline\ItemInterface;

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
        return $this->request ?: $this->item;
    }

    public static function request(string $url, callable $parseCallback): self
    {
        return new self(
            new Request($url, $parseCallback),
            null,
        );
    }

    public function apply(Closure $ifRequest, Closure $ifItem): void
    {
        null !== $this->request
            ? $ifRequest($this->request)
            : $ifItem($this->item);
    }
}
