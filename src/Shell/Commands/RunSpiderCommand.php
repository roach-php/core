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

namespace RoachPHP\Shell\Commands;

use RoachPHP\Roach;
use RoachPHP\Shell\InvalidSpiderException;
use RoachPHP\Shell\Resolver\NamespaceResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunSpiderCommand extends Command
{
    protected static $defaultName = 'roach:run';

    protected static $defaultDescription = 'Start a spider run for the provided spider class';

    protected function configure(): void
    {
        $this->addArgument('spider', InputArgument::REQUIRED, 'The spider class to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resolver = Roach::resolve(NamespaceResolverInterface::class);

        try {
            /** @psalm-suppress MixedArgument */
            $spiderClass = $resolver->resolveSpiderNamespace($input->getArgument('spider'));
        } catch (InvalidSpiderException $exception) {
            $output->writeln(\sprintf('<error>Invalid spider: %s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        Roach::startSpider($spiderClass);

        return self::SUCCESS;
    }
}
