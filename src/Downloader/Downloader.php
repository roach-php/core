<?php

namespace Sassnowski\Roach\Downloader;

use Closure;
use Sassnowski\Roach\Http\ClientInterface;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;

class Downloader
{
    /** @var DownloaderMiddleware[] */
    private array $middleware = [];

    private array $requests = [];

    public function __construct(private ClientInterface $client)
    {
    }

    public function withMiddleware(DownloaderMiddleware ...$middleware): void
    {
        $this->middleware = $middleware;
    }

    public function download(Request $request): void
    {
        foreach ($this->middleware as $middleware) {
            $request = $middleware->handleRequest($request);

            if  ($request->wasDropped()) {
                return;
            }
        }

        $this->requests[] = $request;
    }

    public function flush(?callable $callback = null): void
    {
        $requests = $this->requests;

        $this->requests = [];

        $this->client->pool($requests, function (Response $response) use ($callback) {
            $this->onResponseReceived($response, $callback);
        });
    }

    private function onResponseReceived(Response $response, ?callable $callback)
    {
        foreach ($this->middleware as $middleware) {
            $response = $middleware->handleResponse($response);

            if ($response->wasDropped()) {
                return;
            }
        }

        if ($callback !== null) {
            $callback($response);
        }
    }
}