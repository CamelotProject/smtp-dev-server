<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Command;

use Camelot\SmtpDevServer\Enum\Scheme;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'smtp:server:start',
    description: 'Start an SMTP server.',
)]
class SmtpServerCommand extends ServerCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument('backing', InputOption::VALUE_REQUIRED, 'Storage type (null, memory, mailbox)', 'mailbox')
        ;
    }

    protected function getScheme(): Scheme
    {
        return Scheme::SMTP;
    }

    protected function defaultPort(): int
    {
        return 2525;
    }
}
