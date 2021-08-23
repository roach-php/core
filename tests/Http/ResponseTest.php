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

namespace RoachPHP\Tests\Http;

use PHPUnit\Framework\TestCase;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Tests\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTest;

/**
 * @internal
 */
final class ResponseTest extends TestCase
{
    use DroppableTest;
    use InteractsWithRequestsAndResponses;

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeResponse(
            $this->makeRequest(),
        );
    }
}
