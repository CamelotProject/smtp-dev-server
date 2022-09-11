<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Command;

use Camelot\SmtpDevServer\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'smtp:server:start',
    description: 'Start an SMTP server.',
)]
class SmtpServerCommand extends Command
{
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->addOption('ip', 'i', InputOption::VALUE_REQUIRED, 'TCP/IP address', 'localhost')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port', 2525)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ip = $input->getOption('ip');
        $port = $input->getOption('port');

        $io = new SymfonyStyle($input, $output);
        $io->title("Starting SMTP server on {$ip} {$port}");

        $server = new Server($ip, $port, $this->logger);
        $server->start();
        $io->info('Waiting for connections …');

        $server->listen(function ($clientId, $message) use ($output): void {
            $output->writeln(trim($message));
        });

        return Command::SUCCESS;
    }
}
