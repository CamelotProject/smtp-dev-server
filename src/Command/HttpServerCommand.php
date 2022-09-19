<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Command;

use Camelot\SmtpDevServer\Server;
use Camelot\SmtpDevServer\Socket\ClientFactory;
use Camelot\SmtpDevServer\Socket\HttpSocket;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'http:server:start',
    description: 'Start an HTTP server.',
)]
class HttpServerCommand extends Command
{
    private Server $server;
    private ?LoggerInterface $logger;

    public function __construct(Server $server, ?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->server = $server;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('backing', InputOption::VALUE_REQUIRED, 'Storage type (null, memory)', 'null')
            ->addOption('ip', 'i', InputOption::VALUE_REQUIRED, 'TCP/IP address', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'SMTP port', 2580)
            ->addOption('help', 'h', InputOption::VALUE_NONE, 'Show help')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ip = $input->getOption('ip');
        $port = $input->getOption('port');

        $io->title('Starting HTTP server');
        $io->listing(["Host:\t{$ip}", "Port:\t{$port}"]);

        $clientFactory = new ClientFactory($ip, $port, HttpSocket::class, $this->logger);

        $this->server->start($clientFactory, $output);
        $io->info('Waiting for HTTP connections');

        $this->server->listen();

        return Command::SUCCESS;
    }
}
