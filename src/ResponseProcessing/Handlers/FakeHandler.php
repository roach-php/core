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

namespace Sassnowski\Roach\ResponseProcessing\Handlers;

use Closure;
use PHPUnit\Framework\Assert;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\ItemPipeline\ItemInterface;
use Sassnowski\Roach\ResponseProcessing\MiddlewareInterface;

final class FakeHandler extends Handler implements MiddlewareInterface
{
    private array $responseCalls = [];

    private array $itemCalls = [];

    private array $requestCalls = [];

    public function __construct(
        private ?Closure $handleResponseCallback = null,
        private ?Closure $handleItemCallback = null,
        private ?Closure $handleRequestCallback = null,
    ) {
        parent::__construct();
    }

    public function handleResponse(Response $response): Response
    {
        $this->responseCalls[] = $response;

        if (null !== $this->handleResponseCallback) {
            return ($this->handleResponseCallback)($response);
        }

        return $response;
    }

    public function handleRequest(Request $request, Response $response): Request
    {
        $this->requestCalls[] = $request;

        if (null !== $this->handleRequestCallback) {
            return ($this->handleRequestCallback)($request, $response);
        }

        return $request;
    }

    public function handleItem(ItemInterface $item, Response $response): ItemInterface
    {
        $this->itemCalls[] = $item;

        if (null !== $this->handleItemCallback) {
            return ($this->handleItemCallback)($item, $response);
        }

        return $item;
    }

    public function assertResponseHandled(Response $response): void
    {
        Assert::assertNotEmpty($this->responseCalls);
        Assert::assertContains($response, $this->responseCalls);
    }

    public function assertItemHandled(ItemInterface $item): void
    {
        Assert::assertNotEmpty($this->itemCalls);
        Assert::assertContains($item, $this->itemCalls);
    }

    public function assertNoResponseHandled(): void
    {
        Assert::assertEmpty($this->responseCalls);
    }

    public function assertNoItemHandled(): void
    {
        Assert::assertEmpty($this->itemCalls);
    }

    public function assertResponseNotHandled(Response $response): void
    {
        Assert::assertNotContains($response, $this->responseCalls);
    }

    public function assertItemNotHandled(ItemInterface $item): void
    {
        Assert::assertNotContains($item, $this->itemCalls);
    }

    public function assertRequestHandled(Request $request): void
    {
        Assert::assertNotEmpty($this->requestCalls);
        Assert::assertContains($request, $this->requestCalls);
    }

    public function assertRequestNotHandled(Request $request): void
    {
        Assert::assertNotContains($request, $this->requestCalls);
    }

    public function assertNoRequestHandled(): void
    {
        Assert::assertEmpty($this->requestCalls);
    }
}
