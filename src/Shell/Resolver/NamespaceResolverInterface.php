<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Shell\Resolver;

use RoachPHP\Shell\InvalidSpiderException;
use RoachPHP\Spider\SpiderInterface;

interface NamespaceResolverInterface
{
    /**
     * @throws InvalidSpiderException Thrown if the provided class does not exist
     * @throws InvalidSpiderException thrown if the provided class does not implement SpiderInterface
     *
     * @return class-string<SpiderInterface>
     */
    public function resolveSpiderNamespace(string $spiderClass): string;
}
