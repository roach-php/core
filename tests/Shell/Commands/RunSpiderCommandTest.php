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

namespace RoachPHP\Tests\Shell\Commands;

use PHPUnit\Framework\TestCase;
use RoachPHP\Roach;
use RoachPHP\Shell\Commands\RunSpiderCommand;
use RoachPHP\Tests\Fixtures\TestSpider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class RunSpiderCommandTest extends TestCase
{
    public function testStartRunForProvidedSpider(): void
    {
        $runner = Roach::fake();
        $commandTester = new CommandTester(new RunSpiderCommand());

        $commandTester->execute([
            'spider' => TestSpider::class,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $runner->assertRunWasStarted(TestSpider::class);
    }

    public function testPrintsAnErrorIfTheProvidedSpiderClassWasInvalid(): void
    {
        $commandTester = new CommandTester(new RunSpiderCommand());

        $commandTester->execute([
            'spider' => '::not-a-spider::',
        ]);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
        self::assertStringContainsString('Invalid spider:', $commandTester->getDisplay(true));
    }
}
