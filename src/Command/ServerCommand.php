<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Command;

use const SIGINT;
use const SIGTERM;

use Camelot\SmtpDevServer\Enum\Scheme;
use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Enum\Signal;
use Camelot\SmtpDevServer\Exception\SignalInterrupt;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Output\RetryBar;
use Camelot\SmtpDevServer\Output\ServerOutput;
use Camelot\SmtpDevServer\Server;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use Camelot\SmtpDevServer\Socket\Timeout;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

abstract class ServerCommand extends Command
{
    protected Server $server;
    protected LoggerInterface $logger;
    protected SymfonyStyle $io;
    protected ServerOutput $serverOutput;
    protected AddressInfo $addressInfo;

    private ?SignalRegistry $signalRegistry;
    private RetryBar $retryBar;
    private int $tries = 0;
    private int $retries;

    public function __construct(Server $server, LoggerInterface $logger, ?SignalRegistry $signalRegistry = null)
    {
        parent::__construct();
        $this->server = $server;
        $this->logger = $logger;
        $this->signalRegistry = $signalRegistry;
    }

    protected function configure(): void
    {
        $this
            ->addOption('ip', 'i', InputOption::VALUE_REQUIRED, 'TCP/IP address', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to listen on', $this->defaultPort())
            ->addOption('retries', 'r', InputOption::VALUE_REQUIRED, 'Number of times to retry connecting to the server socket address if it is currently in use', 10)
            ->addOption('wait', 'w', InputOption::VALUE_REQUIRED, 'Seconds to wait before retrying connection to the server socket address if it is currently in use', 10)
            ->addOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug')
            ->addOption('help', 'h', InputOption::VALUE_NONE, 'Show help')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->hasParameterOption('-vvv', true) || $input->hasParameterOption('--verbose=3', true) || $input->getParameterOption('--verbose', false, true) === 3) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        } elseif ($input->hasParameterOption('-vv', true) || $input->hasParameterOption('--verbose=2', true) || $input->getParameterOption('--verbose', false, true) === 2) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        } elseif ($input->hasParameterOption('-v', true) || $input->hasParameterOption('--verbose=1', true) || $input->hasParameterOption('--verbose', true) || $input->getParameterOption('--verbose', false, true)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $this->io = new SymfonyStyle($input, $output);
        $this->serverOutput = new ServerOutput($this->io, $this->logger);
        $this->retries = (int) $input->getOption('retries');
        $this->retryBar = new RetryBar((int) $input->getOption('wait'), $this->io);
        $this->addressInfo = new AddressInfo($this->getScheme(), $input->getOption('ip'), (int) $input->getOption('port'));
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeout = new Timeout(0, 250);

        try {
            $this->signals();

            try {
                $this->start($timeout);
            } catch (SignalInterrupt) {
                $this->server->stop(Shutdown::Immediate);

                return Command::FAILURE;
            }
            try {
                $this->listen();
            } catch (SignalInterrupt) {
                $this->server->stop(Shutdown::Immediate);
            }
        } finally {
            $this->server->stop(Shutdown::Final);
        }

        return Command::SUCCESS;
    }

    abstract protected function getScheme(): Scheme;

    abstract protected function defaultPort(): int;

    /** @throws Throwable */
    private function start(Timeout $timeout): void
    {
        foreach (range(1, $this->retries) as $try) {
            try {
                $this->server->start($this->addressInfo, $timeout, $this->serverOutput);
                break;
            } catch (SocketException $e) {
                if ($e->getCode() === SOCKET_EADDRINUSE && ++$this->tries <= $this->retries) {
                    if ($try === 1) {
                        $this->io->newLine();
                    }
                    $this->retryBar->timeout($try);
                    continue;
                }

                $this->startFailed($e);
            } catch (\Throwable $e) {
                $this->startFailed($e);
            }
        }
    }

    private function listen(): int
    {
        try {
            $this->server->listen();
        } catch (SignalInterrupt $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->io->error($e->getMessage());

            return match (true) {
                $this->io->isVerbose() => throw $e,
                default => Command::FAILURE,
            };
        }

        return Command::SUCCESS;
    }

    private function signals(): void
    {
        if ($this->signalRegistry === null) {
            return;
        }

        $stop = function (int $signal, int $hasNext): void {
            $signal = Signal::from($signal);
            $this->io->info("Caught {$signal->name} signal");

            throw new SignalInterrupt($signal, $hasNext);
        };

        $this->signalRegistry->register(SIGTERM, $stop);
        $this->signalRegistry->register(SIGINT, $stop);
    }

    /** @throws Throwable */
    private function startFailed(Throwable $e): void
    {
        $this->io->error(['Exception thrown starting server.', $e->getMessage()]);

        if ($this->io->isVerbose()) {
            throw $e;
        }
    }
}
