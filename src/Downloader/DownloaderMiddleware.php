<?php

namespace Sassnowski\Roach\Downloader;

use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

interface DownloaderMiddleware
{
    public function handleRequest(Request $request): Request;

    public function handleResponse(Response $response): Response;
}