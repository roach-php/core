<?php

use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Roach;
use Sassnowski\Roach\Spider\AbstractSpider;

require __DIR__ . '/../vendor/autoload.php';

class FussballdatenSpider extends AbstractSpider
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