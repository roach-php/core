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

namespace RoachPHP\Shell\Resolver;

use RoachPHP\Spider\SpiderInterface;

/**
 * @internal
 */
final class FakeNamespaceResolver implements NamespaceResolverInterface
{
    /**
     * @param class-string<SpiderInterface> $spiderClass
     *
     * @return class-string<SpiderInterface>
     */
    public function resolveSpiderNamespace(string $spiderClass): string
    {
        return $spiderClass;
    }
}
