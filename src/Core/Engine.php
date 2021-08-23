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

namespace RoachPHP\Core;

use RoachPHP\Downloader\Downloader;
use RoachPHP\Events\RunFinished;
use RoachPHP\Events\RunStarting;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\ItemPipelineInterface;
use RoachPHP\ResponseProcessing\ParseResult;
use RoachPHP\ResponseProcessing\Processor;
use RoachPHP\Scheduling\RequestSchedulerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Engine
{
    public function __construct(
        private RequestSchedulerInterface $scheduler,
        private Downloader $downloader,
        private ItemPipelineInterface $itemPipeline,
        private Processor $responseProcessor,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function start(Run $run): void
    {
        $this->eventDispatcher->dispatch(
            new RunStarting($run),
            RunStarting::NAME,
        );

        $this->configure($run);

        foreach ($run->startRequests() as $request) {
            $this->scheduleRequest($request);
        }

        $this->work($run);
    }

    private function work(Run $run): void
    {
        while (!$this->scheduler->empty()) {
            foreach ($this->scheduler->nextRequests() as $request) {
                $this->downloader->prepare($request);
            }

            $this->downloader->flush(
                fn (Response $response) => $this->onFulfilled($response, $run),
            );
        }

        $this->eventDispatcher->dispatch(
            new RunFinished($run),
            RunFinished::NAME,
        );
    }

    private function onFulfilled(Response $response, Run $run): void
    {
        /** @var ParseResult[] $parseResults */
        $parseResults = $this->responseProcessor->handle($response);

        foreach ($parseResults as $result) {
            $result->apply(
                fn (Request $request) => $this->scheduleRequest($request),
                fn (ItemInterface $item) => $this->itemPipeline->sendItem($item),
            );
        }
    }

    private function scheduleRequest(Request $request): void
    {
        $this->scheduler->schedule($request);
    }

    private function configure(Run $run): void
    {
        $this->scheduler->setBatchSize($run->concurrency());
        $this->scheduler->setDelay($run->requestDelay());
        $this->itemPipeline->setProcessors(...$run->itemProcessors());
        $this->downloader->withMiddleware(...$run->downloaderMiddleware());
        $this->responseProcessor->withMiddleware(...$run->responseMiddleware());
    }
}
