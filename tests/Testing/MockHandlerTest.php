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

namespace Sassnowski\Roach\Tests\Testing;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Sassnowski\Roach\Testing\FakeHandler;
use Sassnowski\Roach\Tests\InteractsWithRequests;

/**
 * @internal
 */
final class MockHandlerTest extends TestCase
{
    use InteractsWithRequests;

    private FakeHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new FakeHandler();
    }

    public function testAssertPassesWhenCalledWithCorrectPayload(): void
    {
        $request = $this->createRequest();

        ($this->handler)($request);

        $this->handler->assertWasCalledWith($request);
    }

    public function testAssertFailsWhenCalledWithIncorrectPayload(): void
    {
        $requestA = $this->createRequest('::url-a::');
        $requestB = $this->createRequest('::url-b::');

        ($this->handler)($requestA);

        $this->expectException(AssertionFailedError::class);
        $this->handler->assertWasCalledWith($requestB);
    }

    public function testAssertFailsWhenNotCalledAtAll(): void
    {
        $request = $this->createRequest();

        $this->expectException(AssertionFailedError::class);
        $this->handler->assertWasCalledWith($request);
    }
}
