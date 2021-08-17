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

namespace Sassnowski\Roach\Spider;

use Exception;
use Sassnowski\Roach\Http\Request;

final class ParseResult
{
    private function __construct(private ?Request $request, private $item)
    {
    }

    public static function item($item): self
    {
        return new self(null, $item);
    }

    public static function request(string $url, callable $parseCallback): self
    {
        return new self(
            new Request($url, $parseCallback),
            null,
        );
    }

    public function isRequest(): bool
    {
        return null !== $this->request;
    }

    public function isItem(): bool
    {
        return null !== $this->item;
    }

    public function getRequest(): Request
    {
        if (!$this->isRequest()) {
            throw new Exception('no request');
        }

        return $this->request;
    }

    public function getItem(): mixed
    {
        if (!$this->isItem()) {
            throw new Exception('no item');
        }

        return $this->item;
    }
}
