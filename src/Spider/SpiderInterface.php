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

namespace RoachPHP\Spider;

use RoachPHP\Http\Request;
use RoachPHP\Spider\Configuration\Configuration;

interface SpiderInterface
{
    public function loadConfiguration(): Configuration;

    /**
     * Provides an override configuration the spider is supposed to
     * use instead of its own configuration. This should modify the
     * existing instance.
     */
    public function withConfiguration(Configuration $configuration): void;

    /**
     * Sets the context for the current run for the spider. This should
     * modify the existing instance.
     */
    public function withContext(array $context): void;

    /**
     * @return array<Request>
     */
    public function getInitialRequests(): array;
}
