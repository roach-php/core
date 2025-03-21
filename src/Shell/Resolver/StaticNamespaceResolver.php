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

use RoachPHP\Shell\InvalidSpiderException;
use RoachPHP\Spider\SpiderInterface;

final class StaticNamespaceResolver implements NamespaceResolverInterface
{
    /**
     * @param class-string<SpiderInterface> $spiderClass
     *
     * @throws \ReflectionException
     * @throws InvalidSpiderException
     *
     * @return class-string<SpiderInterface>
     */
    public function resolveSpiderNamespace(string $spiderClass): string
    {
        if (!\class_exists($spiderClass)) {
            throw new InvalidSpiderException("The spider class {$spiderClass} does not exist");
        }

        if (!$this->isSpider($spiderClass)) {
            throw new InvalidSpiderException("The class {$spiderClass} is not a spider");
        }

        return $spiderClass;
    }

    /**
     * @param class-string<SpiderInterface> $spiderClass
     *
     * @throws \ReflectionException
     */
    private function isSpider(string $spiderClass): bool
    {
        return (new \ReflectionClass($spiderClass))->implementsInterface(SpiderInterface::class);
    }
}
