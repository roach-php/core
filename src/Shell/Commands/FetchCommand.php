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

namespace RoachPHP\Shell\Commands;

use GuzzleHttp\Client;
use Psy\Command\Command;
use Psy\Shell;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'fetch')]
final class FetchCommand extends Command
{
    protected static string $defaultName = 'fetch';

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new Client();

        /**
         * @psalm-suppress MixedAssignement
         *
         * @var string
         */
        $url = $input->getArgument('url');
        $request = new Request('GET', $url, static fn () => yield from []);
        $response = new Response(
            $client->send($request->getPsrRequest()),
            $request,
        );

        $output->writeln(
            <<<TEXT
<info>
Available variables:
    \$response:      <{$response->getStatus()} '{$url}'>
    \$html:          Raw HTML contents of response
Commands:
    fetch <url>     Fetch URL and update the \$response and \$html objects
</info>
TEXT
        );

        /** @var Shell $app */
        $app = $this->getApplication();
        $app->setScopeVariables([
            'response' => $response,
            'html' => $response->getBody(),
        ]);

        return 0;
    }
}
