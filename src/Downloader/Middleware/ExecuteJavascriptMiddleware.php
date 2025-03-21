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

use Psr\Log\LoggerInterface;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;
use Spatie\Browsershot\Browsershot;

final class ExecuteJavascriptMiddleware implements ResponseMiddlewareInterface
{
    use Configurable;

    /**
     * @var callable(string): Browsershot
     */
    private $getBrowsershot;

    /**
     * @param  null|callable(string): Browsershot  $getBrowsershot
     */
    public function __construct(
        private LoggerInterface $logger,
        ?callable $getBrowsershot = null,
    ) {
        $this->getBrowsershot = $getBrowsershot ?? static fn (string $uri): Browsershot => Browsershot::url($uri)->waitUntilNetworkIdle();
    }

    public function handleResponse(Response $response): Response
    {
        $browsershot = $this->configureBrowsershot(
            $response->getRequest()->getUri(),
        );

        try {
            $body = $browsershot->bodyHtml();
        } catch (\Throwable $e) {
            $this->logger->info('[ExecuteJavascriptMiddleware] Error while executing javascript', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $response->drop('Error while executing javascript');
        }

        return $response->withBody($body);
    }

    /**
     * @psalm-suppress MixedArgument, MixedAssignment
     */
    private function configureBrowsershot(string $uri): Browsershot
    {
        $browsershot = ($this->getBrowsershot)($uri);

        if (! empty($this->option('chromiumArguments'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->addChromiumArguments($this->option('chromiumArguments'));
        }

        if (null !== ($chromePath = $this->option('chromePath'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setChromePath($chromePath);
        }

        if (null !== ($binPath = $this->option('binPath'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setBinPath($binPath);
        }

        if (null !== ($nodeModulePath = $this->option('nodeModulePath'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        if (null !== ($includePath = $this->option('includePath'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setIncludePath($includePath);
        }

        if (null !== ($nodeBinary = $this->option('nodeBinary'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setNodeBinary($nodeBinary);
        }

        if (null !== ($npmBinary = $this->option('npmBinary'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setNpmBinary($npmBinary);
        }

        if (null !== ($userAgent = $this->option('userAgent'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->userAgent($userAgent);
        }

        if (! empty($delay = $this->option('delay'))) {
            /** @phpstan-ignore argument.type */
            $browsershot->setDelay($delay);
        }

        return $browsershot;
    }

    private function defaultOptions(): array
    {
        return [
            'chromiumArguments' => [],
            'chromePath' => null,
            'binPath' => null,
            'nodeModulePath' => null,
            'includePath' => null,
            'nodeBinary' => null,
            'npmBinary' => null,
            'userAgent' => null,
            'delay' => 0,
        ];
    }
}
