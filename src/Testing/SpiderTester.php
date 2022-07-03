<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Testing;

use Closure;
use Generator;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\SpiderInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

final class SpiderTester
{
    use InteractsWithRequestsAndResponses;

    /**
     * @var array<string, mixed>
     */
    private array $requestMeta = [];

    private string $baseURL = 'https://example.com';

    public function __construct(private SpiderInterface $spider)
    {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function withRequestMeta(array $meta): self
    {
        $this->requestMeta = $meta;

        return $this;
    }

    public function withBaseURL(string $baseURL): self
    {
        $this->baseURL = $baseURL;

        return $this;
    }

    public function parseHTMLFile(
        string $filename,
        string $parseMethod = 'parse',
    ): SpiderTestResult {
        if (!\file_exists($filename)) {
            throw new \RuntimeException("File {$filename} does not exist");
        }

        $html = \file_get_contents($filename);

        return $this->parseHTMLString($html, $parseMethod);
    }

    public function parseHTMLString(
        string $html,
        string $parseMethod = 'parse',
    ): SpiderTestResult {
        $request = $this->createRequest($parseMethod);

        $response = $this->makeResponse($request, body: $html);

        return new SpiderTestResult(
            $this->getResults($request, $response),
        );
    }

    private function createRequest(string $parseMethod): Request
    {
        if (!\method_exists($this->spider, $parseMethod)) {
            throw new \InvalidArgumentException(\sprintf(
                'Method "%s" does not exist on spider %s',
                $parseMethod,
                $this->spider::class,
            ));
        }

        /** @var Closure(Response): Generator<int, ParseResult> $callback */
        $callback = Closure::fromCallable([$this->spider, $parseMethod]);
        $request = new Request('GET', $this->baseURL, $callback);

        /** @psalm-suppress MixedAssignment */
        foreach ($this->requestMeta as $key => $value) {
            $request = $request->withMeta($key, $value);
        }

        return $request;
    }

    /**
     * @return array<int, ParseResult>
     */
    private function getResults(Request $request, Response $response): array
    {
        $results = [];

        foreach ($request->callback($response) as $result) {
            $results[] = $result;
        }

        return $results;
    }
}
