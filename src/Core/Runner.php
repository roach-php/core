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

namespace RoachPHP\Core;

use Psr\Container\ContainerInterface;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Spider\SpiderInterface;

final class Runner implements RunnerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private EngineInterface $engine,
    ) {
    }

    public function startSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): void
    {
        $this->engine->start($this->createRun($spiderClass, $overrides, $context));
    }

    public function collectSpider(string $spiderClass, ?Overrides $overrides = null, array $context = []): array
    {
        return $this->engine->collect($this->createRun($spiderClass, $overrides, $context));
    }

    private function createRun(string $spiderClass, ?Overrides $overrides, array $context): Run
    {
        /** @var SpiderInterface $spider */
        $spider = $this->container->get($spiderClass);

        $spider->withContext($context);

        return (new RunFactory($this->container))->fromSpider($spider, $overrides);
    }
}
