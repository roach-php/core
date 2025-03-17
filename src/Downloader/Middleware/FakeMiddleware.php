<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Downloader\Middleware;

use Exception;
use PHPUnit\Framework\Assert;
use RoachPHP\Downloader\DownloaderMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

/**
 * @internal
 */
final class FakeMiddleware implements DownloaderMiddlewareInterface
{
    use Configurable;

    /**
     * @var array Request[]
     */
    private array $requestsHandled = [];

    /**
     * @var array Response[]
     */
    private array $responsesHandled = [];

    /**
     * @var array Exception[]
     */
    private array $exceptionsHandled = [];

    /**
     * @param ?\Closure(Request): Request   $requestHandler
     * @param ?\Closure(Response): Response $responseHandler
     */
    public function __construct(
        private ?\Closure $requestHandler = null,
        private ?\Closure $responseHandler = null,
        private ?\Closure $exceptionHandler = null,
    ) {
    }

    public function handleRequest(Request $request): Request
    {
        $this->requestsHandled[] = $request;

        if (null !== $this->requestHandler) {
            return ($this->requestHandler)($request);
        }

        return $request;
    }

    public function handleResponse(Response $response): Response
    {
        $this->responsesHandled[] = $response;

        if (null !== $this->responseHandler) {
            return ($this->responseHandler)($response);
        }

        return $response;
    }

    public function handleException(Exception $exception, Request $request): ?Request
    {
        $this->exceptionsHandled[] = $exception;

        if (null !== $this->exceptionHandler) {
            return ($this->exceptionHandler)($exception, $request);
        }

        return $request;
    }

    public function assertRequestHandled(Request $request): void
    {
        Assert::assertContains($request, $this->requestsHandled);
    }

    public function assertRequestNotHandled(Request $request): void
    {
        Assert::assertNotContains($request, $this->requestsHandled);
    }

    public function assertNoRequestsHandled(): void
    {
        Assert::assertEmpty($this->requestsHandled);
    }

    public function assertResponseHandled(Response $response): void
    {
        Assert::assertContains($response, $this->responsesHandled);
    }

    public function assertResponseNotHandled(Response $response): void
    {
        Assert::assertNotContains($response, $this->responsesHandled);
    }

    public function assertNoResponseHandled(): void
    {
        Assert::assertEmpty($this->responsesHandled);
    }

    public function assertExceptionHandled(Exception $exception): void
    {
        Assert::assertContains($exception, $this->exceptionsHandled);
    }

    public function assertExceptionNotHandled(Exception $exception): void
    {
        Assert::assertNotContains($exception, $this->exceptionsHandled);
    }

    public function assertNoExceptionHandled(): void
    {
        Assert::assertEmpty($this->exceptionsHandled);
    }
}
