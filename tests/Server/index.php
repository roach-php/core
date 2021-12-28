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

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

const LOG_PATH = __DIR__ . '/tmp/crawled.json';

$app = AppFactory::create();

$app->add(static function (ServerRequestInterface $request, RequestHandlerInterface $handler): Response {
    $ignoredRoutes = ['/ping', '/crawled-routes'];
    $path = $request->getUri()->getPath();

    if (\in_array($path, $ignoredRoutes, true)) {
        return $handler->handle($request);
    }

    if (!\file_exists(LOG_PATH)) {
        \file_put_contents(LOG_PATH, '{}');
    }

    try {
        $logs = \json_decode(\file_get_contents(LOG_PATH), true, 512, \JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $logs = [];
    }

    if (!isset($logs[$path])) {
        $logs[$path] = 0;
    }

    ++$logs[$path];
    \file_put_contents(__DIR__ . '/tmp/crawled.json', \json_encode($logs, \JSON_THROW_ON_ERROR));

    return $handler->handle($request);
});

$app->get('/ping', static function (Request $request, Response $response, $args) {
    $response->getBody()->write('pong');

    return $response;
});

$app->get('/crawled-routes', static function (Request $request, Response $response, $args): Response {
    $stats = \file_get_contents(LOG_PATH);

    $response->getBody()->write($stats);

    return $response
        ->withHeader('Content-Type', 'application/json');
});

$app->get('/robots', static function (Request $request, Response $response, $args): Response {
    $robots = <<<'PLAIN'
User-agent: *
Disallow: /test2
PLAIN;

    $response->getBody()->write($robots);

    return $response->withAddedHeader('Content-type', 'text/plain');
});

$app->get('/test1', static function (Request $request, Response $response, $args) {
    $response->getBody()->write('<div><h1 id="headline">Such headline, wow</h1></div>');

    return $response;
});

$app->get('/test2', static function (Request $request, Response $response, $args) {
    $response->getBody()->write('<div><a href="/test3">Link</a><a href="/test1">Link</a></div>');

    return $response;
});

$app->get('/test3', static function (Request $request, Response $response, $args) {
    return $response;
});

$app->get('/javascript', static function (Request $request, Response $response, $args) {
    $body = <<<HTML
<div id="content">Loading...</div>
<script>
    const content = document.getElementById('content');
    content.innerHTML = '<h1>Headline</h1><p>I was loaded via Javascript!</p>'
</script>
HTML;

    $response->getBody()->write($body);

    return $response;
});

$app->run();
