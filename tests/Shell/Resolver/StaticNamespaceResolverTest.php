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

namespace RoachPHP\Tests\Shell\Resolver;

use PHPUnit\Framework\TestCase;
use RoachPHP\Shell\InvalidSpiderException;
use RoachPHP\Shell\Resolver\StaticNamespaceResolver;
use RoachPHP\Tests\Fixtures\RequestSpiderMiddleware;

/**
 * @internal
 */
final class StaticNamespaceResolverTest extends TestCase
{
    public function testUseProvidedParameterAsIsIfItExistsAndIsAValidSpider(): void
    {
        $resolver = new StaticNamespaceResolver();

        $result = $resolver->resolveSpiderNamespace('RoachPHP\Tests\Fixtures\TestSpider');

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    public function testThrowsExceptionIfTheProvidedSpiderClassDoesNotExist(): void
    {
        $resolver = new StaticNamespaceResolver();

        $this->expectException(InvalidSpiderException::class);
        $this->expectErrorMessage('The spider class ::spider-class:: does not exist');

        $resolver->resolveSpiderNamespace('::spider-class::');
    }

    public function testThrowsExceptionIfTheProvidedClassIsNotASpider(): void
    {
        $resolver = new StaticNamespaceResolver();

        $this->expectException(InvalidSpiderException::class);
        $this->expectErrorMessage(\sprintf('The class %s is not a spider', RequestSpiderMiddleware::class));

        $resolver->resolveSpiderNamespace(RequestSpiderMiddleware::class);
    }
}
