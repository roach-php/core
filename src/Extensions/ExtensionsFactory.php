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

namespace RoachPHP\Extensions;

use Psr\Container\ContainerInterface;
use RoachPHP\Core\Run;

/**
 * @internal
 */
final class ExtensionsFactory
{
    private array $coreExtensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @return Extension[]
     */
    public function buildExtensionsForRun(Run $run): array
    {
        return \array_map(
            /** @psalm-suppress MixedReturnStatement,MixedInferredReturnType */
            fn (string $extension): Extension => $this->container->get($extension),
            $this->coreExtensions,
        );
    }
}
