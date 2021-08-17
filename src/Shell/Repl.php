<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/roach
 */

namespace Sassnowski\Roach\Shell;

use Psy\Configuration;
use Psy\Shell;
use Sassnowski\Roach\Http\Response;
use Sassnowski\Roach\Shell\Commands\FetchCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

final class Repl extends Command
{
    protected static $defaultName = 'roach:shell';

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'The URL to fetch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->setOption('ansi', true);

        $url = $input->getArgument('url');

        $config = Configuration::fromInput($input);
        $config->addCasters([
            Crawler::class => 'Sassnowski\Roach\Shell\ShellCaster::castCrawler',
            Link::class => 'Sassnowski\Roach\Shell\ShellCaster::castLink',
            Response::class => 'Sassnowski\Roach\Shell\ShellCaster::castResponse',
        ]);
        $config->addCommands([new FetchCommand()]);

        $shell = new Shell($config);

        $command = $shell->find('fetch');
        $command->run(new ArrayInput(['url' => $url]), $output);

        $shell->run();

        return 0;
    }
}
