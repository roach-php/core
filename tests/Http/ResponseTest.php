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

namespace Sassnowski\Roach\Tests\Http;

use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Support\DroppableInterface;
use Sassnowski\Roach\Tests\InteractsWithRequestsAndResponses;
use Sassnowski\Roach\Tests\Support\DroppableTest;

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
