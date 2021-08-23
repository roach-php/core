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

use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Roach;
use Sassnowski\Roach\Spider\BasicSpider;

require __DIR__ . '/../vendor/autoload.php';

final class BlogSpider extends BasicSpider
{
    public array $startUrls = [
        'https://kai-sassnowski.com',
        'https://kai-sassnowski.com/open-source',
        'https://kai-sassnowski.com/projects',
        'https://kai-sassnowski.com/about',
    ];

    public array $downloaderMiddleware = [];

    public function parse(Response $response): Generator
    {
        yield $this->item([
            'title' => $response->filter('title')->text(),
        ]);
    }
}

Roach::startSpider(BlogSpider::class);
