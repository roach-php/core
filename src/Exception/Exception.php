<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Pavlo Komarov
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Exception;

use RoachPHP\Http\Request;

class Exception extends \Exception
{
    private Request $request;

    public function __construct(Request $request, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }
}
