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

use Sassnowski\Roach\Http\Middleware\LoggerMiddleware;
use Sassnowski\Roach\Http\Middleware\RequestDeduplicationMiddleware;
use Sassnowski\Roach\Http\Middleware\UserAgentMiddleware;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Roach;
use Sassnowski\Roach\Spider\AbstractSpider;

require __DIR__ . '/../vendor/autoload.php';

final class test extends AbstractSpider
{
    protected array $startUrls = [
        'https://kai-sassnowski.com',
    ];

    protected array $middleware = [
        RequestDeduplicationMiddleware::class,
        [UserAgentMiddleware::class, ['userAgent' => 'roach test']],
        LoggerMiddleware::class,
    ];

    public function parse(Response $response): Generator
    {
        yield $this->request('https://kai-sassnowski.com/projects');

        yield $this->request('https://kai-sassnowski.com/open-source');
    }

    public function parsePost(Response $response): Generator
    {
        $links = $response->filter('a')->links();

        foreach ($links as $link) {
            yield $this->request($link->getUri());
        }
    }
}

Roach::startSpider(BlogSpider::class);
