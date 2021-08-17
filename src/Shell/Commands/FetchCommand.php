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

namespace Sassnowski\Roach\Shell\Commands;

use GuzzleHttp\Client;
use Psy\Command\Command;
use Sassnowski\Roach\Http\Request;
use Sassnowski\Roach\Http\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchCommand extends Command
{
    protected static $defaultName = 'fetch';

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client();
        $url = $input->getArgument('url');
        $request = new Request($url, static function (): void {
        });
        $response = new Response($client->send($request), $request);

        $output->writeln(
            <<<TEXT
<info>
Available variables:
    \$response:      <{$response->getStatus()} '{$url}'>
    \$html:          Raw HTML contents of response
Commands:
    fetch <url>     Fetch URL and update \$response object
</info>
TEXT
        );

        $this
            ->getApplication()
            ->setScopeVariables([
                'response' => $response,
                'html' => $response->getBody(),
            ]);

        return 0;
    }
}
