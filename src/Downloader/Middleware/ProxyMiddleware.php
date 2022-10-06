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

namespace App\Downloader\Middleware;

use Psr\Log\LoggerInterface;
use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Support\Configurable;

final class ProxyMiddleware implements RequestMiddlewareInterface
{
	use Configurable;

	public function __construct(private readonly LoggerInterface $logger)
	{
	}

	public function handleRequest(Request $request): Request
	{
		$proxyList =  $this->option('proxyList');

		if (empty($this->option('proxyList'))) {
			return $request;
		}

		if (count($proxyList) == 1) {
			$request->addOption('proxy', $proxyList[0]);
			$this->logger->info(
				'[ProxyMiddleware] Use proxy for request',
				[
					'proxy' => $proxyList[0],
					'uri' => $request->getUri(),
				],
			);
		}

		return $request;
	}

	private function defaultOptions(): array
	{
		return [
			'proxyList' => [],
		];
	}
}
