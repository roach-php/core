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

namespace Sassnowski\Roach\ItemPipeline\Processors;

use const FILE_APPEND;

final class JsonExporter
{
    public function __construct(private string $filePath)
    {
    }

    public function processItem(mixed $item): mixed
    {
        \file_put_contents(
            $this->filePath,
            \json_encode($item, \JSON_THROW_ON_ERROR) . \PHP_EOL,
            FILE_APPEND,
        );
    }
}
