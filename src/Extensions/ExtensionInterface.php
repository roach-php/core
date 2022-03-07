<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Extensions;

use RoachPHP\Support\ConfigurableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface ExtensionInterface extends ConfigurableInterface, EventSubscriberInterface
{
}
