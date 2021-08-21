<?php

namespace Sassnowski\Roach\Downloader;

use Psr\Log\LoggerInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

class LoggerMiddleware implements DownloaderMiddleware
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handleRequest(Request $request): Request
    {
        $this->logger->info('Got request...');

        return $request;
    }

    public function handleResponse(Response $response): Response
    {
        $this->logger->info('Got response...');

        return $response;
    }
}