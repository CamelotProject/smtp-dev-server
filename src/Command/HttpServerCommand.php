<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Command;

use Camelot\SmtpDevServer\Enum\Scheme;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'http:server:start',
    description: 'Start an HTTP server.',
)]
class HttpServerCommand extends ServerCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument('backing', InputOption::VALUE_REQUIRED, 'Storage type (null, memory)', 'null')
        ;
    }

    protected function getScheme(): Scheme
    {
        return Scheme::HTTP;
    }

    protected function defaultPort(): int
    {
        return 2580;
    }
}
