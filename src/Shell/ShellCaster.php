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

namespace Sassnowski\Roach\Shell;

use Sassnowski\Roach\Http\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\VarDumper\Caster\Caster;

final class ShellCaster
{
    public static function castResponse(Response $response): array
    {
        return [
            Caster::PREFIX_VIRTUAL . '.status' => $response->getStatus(),
            Caster::PREFIX_VIRTUAL . '.uri' => $response->getUri(),
        ];
    }

    public static function castCrawler(Crawler $crawler): array
    {
        return [
            Caster::PREFIX_VIRTUAL . '.count' => $crawler->count(),
            Caster::PREFIX_VIRTUAL . '.html' => $crawler->outerHtml(),
        ];
    }

    public static function castLink(Link $link): array
    {
        return [
            CASTER::PREFIX_PROTECTED . '.uri' => $link->getUri(),
        ];
    }
}
