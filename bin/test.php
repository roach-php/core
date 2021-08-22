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
use Sassnowski\Roach\Spider\AbstractSpider;

require __DIR__ . '/../vendor/autoload.php';

final class test extends AbstractSpider
{
    public static int $concurrency = 1;

    public static int $requestDelay = 2;

    protected array $startUrls = [
        'https://kai-sassnowski.com/projects',
        'https://kai-sassnowski.com/projects',
        'https://kai-sassnowski.com/open-source',
        'https://kai-sassnowski.com/about',
    ];

    public function parse(Response $response): Generator
    {
        yield $this->item(['foo' => 'bar']);
    }
}

Roach::startSpider(FussballdatenSpider::class);
