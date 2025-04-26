<?php

declare(strict_types=1);

namespace RoachPHP\Tests\Http;

use PHPUnit\Framework\TestCase;
use RoachPHP\Http\ExceptionContext;
use RoachPHP\Http\RequestException;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @group http
 *
 * @internal
 */
final class RequestExceptionTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function testHandled(): void
    {
        $exception = new RequestException($this->makeRequest(), new \Exception());

        $this->assertFalse($exception->isHandled());

        $exception = $exception->setHandled();
        $this->assertTrue($exception->isHandled());
    }
}
