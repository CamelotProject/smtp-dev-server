<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Output;

use const PHP_EOL;

use Camelot\SmtpDevServer\Enum\OutputPrefix;
use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Enum\SocketAction;
use Camelot\SmtpDevServer\Event\SocketEvent;
use Camelot\SmtpDevServer\Exception\ServerFatalRuntimeException;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Exception\SignalInterrupt;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Helper;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use Camelot\SmtpDevServer\Socket\Connection;
use Camelot\SmtpDevServer\Socket\Connections;
use Camelot\SmtpDevServer\Socket\Listener;
use Camelot\SmtpDevServer\Socket\RemoteSocketInterface;
use Camelot\SmtpDevServer\Socket\SocketInterface;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class ServerOutput implements ServerOutputInterface
{
    private StyleInterface $output;
    private LoggerInterface $logger;

    public function __construct(OutputInterface $output, LoggerInterface $logger)
    {
        $output->setFormatter(new ServerOutputFormatter($output->getFormatter()));
        $this->output = new SymfonyStyle(new ArgvInput(), $output);
        $this->logger = $logger;
    }

    public function startup(AddressInfo $addressInfo): void
    {
        $this->output->title('Starting ' . $addressInfo->getScheme()->name . ' server');
        $this->logger->notice(sprintf('[STARTED] %s', $addressInfo->formatted()));
    }

    public function startupFailed(Throwable $e): void
    {
        $this->output->error(['Exception thrown starting server.', $e::class, $e->getMessage()]);
        $this->logger->emergency(sprintf('Exception thrown starting server: %s %s', $e::class, $e->getMessage()), ['exception' => $e, 'trace' => $e->getTrace()]);
    }

    public function listening(AddressInfo $addressInfo, int|string $listenerId): void
    {
        $this->output->info('Listening for connections');
        $this->output->listing([
            "ID:\t\t{$listenerId}",
            "Host:\t{$addressInfo->getAddress()}",
            "Port:\t{$addressInfo->getPort()}",
        ]);
        $this->logger->notice(sprintf('[LISTENING] %s (ID %s)', $addressInfo->formatted(), $listenerId));
    }

    public function shutdown(AddressInfo $addressInfo, Shutdown $shutdown): void
    {
        $this->logger->notice(sprintf('[STOPPING] %s', $addressInfo->formatted()));
        if ($shutdown->isNormal()) {
            $this->output->newLine();
            $this->output->title('Initiating shutdown');
        }
    }

    public function shutdownFinal(AddressInfo $addressInfo, Listener $listener, Connections $connections, Shutdown $shutdown): void
    {
        if ($shutdown->isFinal()) {
            $this->logger->notice(sprintf('[STOPPED] %s', $addressInfo->formatted()));
            $this->stats($listener);
            $this->notice("<statistics> * Connections\t{$connections->total()}</>");
            $this->output->newLine();
            $this->output->success('Server shutdown complete');
        }
    }

    public function connection(Connection $connection): void
    {
        $this->warning(sprintf('<connect>%s %s => %s</>', OutputPrefix::CONNECT->string(), $connection->remoteName(), $connection->localName()));
        $this->logger->notice('Connection created ' . $connection->remoteName());
    }

    public function dispatch(SocketEvent $event, SocketAction $action): void
    {
        if ($this->output->isVerbose()) {
            $this->output->write(sprintf('<dispatch>%s %s => %s</>', OutputPrefix::DISPATCH->string(), $event->getConnectionId(), str_pad($action->name, 20)));
        }
    }

    public function dispatched(Throwable $e = null): void
    {
        if ($this->output->isVerbose()) {
            match ($e) {
                null => $this->output->writeln('<success>[SUCCESS]</>'),
                default => $this->output->writeln('<failure>[FAILURE]</>') ?? $this->exception($e),
            };
        }
    }

    public function request(string|Stringable $request, SocketEvent $event): void
    {
        $this->info(sprintf('<read>%s %s => Read %s bytes</>', OutputPrefix::READ->string(), $event->getConnectionId(), number_format(\strlen("{$request}"))));
        if ($this->output->isDebug()) {
            $messages = Helper::bufferToLines($request);
            if ($messages[array_key_last($messages)] === "\r\n") {
                array_pop($messages);
            }
            foreach ($messages as $message) {
                $this->output->writeln(sprintf('<read>%s</>', $message));
            }
        }
        $this->logger->debug('Reading', ['local' => $event->getLocalName(), 'remote' => $event->getRemoteName(), 'data' => $request]);
    }

    public function response(string|Stringable $response, int $bytesWritten, SocketEvent $event): void
    {
        $statusCode = \is_object($response) && method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 0;
        $message = sprintf('<write>%s %s => Response code %s. Wrote %s bytes</>', OutputPrefix::WRITE->string(), $event->getConnectionId(), $statusCode, number_format($bytesWritten));
        if ($this->output->isDebug()) {
            // FIXME — do I want to dump the whole response? Maybe less than, or only first, 1024 bytes?
            $this->output->writeln($message);
        } else {
            $this->info($message);
        }
        $this->logger->debug('Writing', ['local' => $event->getLocalName(), 'remote' => $event->getRemoteName(), 'data' => "{$response}"]);
    }

    public function disconnection(SocketInterface $socket): void
    {
        $report = $socket instanceof Connection ? "{$socket->remoteName()} => {$socket->localName()}" : $socket->localName();

        $this->warning(sprintf('<disconnect>%s %s</>', OutputPrefix::DISCONNECT->string(), $report));
        $this->logger->notice("Disconnected {$report}");
    }

    public function termination(Connection $socket): void
    {
        $report = sprintf('%s => Closed remote socket', $socket->name());
        $this->warning(sprintf('<terminate>%s %s</>', OutputPrefix::TERMINATE->string(), $report));
        $this->logger->notice($report);
    }

    public function stats(SocketInterface $socket): void
    {
        $memory = Format::memory($socket->stats()->memory());
        $duration = Format::time($socket->stats()->duration() / 1000);
        $received = Format::memory($socket->bytes()->received());
        $sent = Format::memory($socket->bytes()->sent());

        if ($socket instanceof RemoteSocketInterface) {
            $reports = sprintf('%s %s => Received: %s | Sent: %s | Duration: %s | Memory: %s', OutputPrefix::STATISTICS->string(), $socket->name(), $received, $sent, $duration, $memory);
        } else {
            $reports = [
                " * Received\t{$received}",
                " * Sent\t\t{$sent}",
                " * Duration\t{$duration}",
                " * Memory\t{$memory}",
            ];
        }
        foreach ((array) $reports as $report) {
            $this->notice("<statistics>{$report}</>");
        }

        $context = ['received' => $received, 'sent' => $sent, 'duration' => $duration, 'memory' => $memory];
        $this->logger->info('Connection report for ' . $socket->name(), $context);
    }

    public function status(Listener $listener, Connections $connections): void
    {
        $this->info(sprintf('<statistics>%s %s <= Active %s | Total %s</>', OutputPrefix::STATISTICS->string(), $listener->name(), $connections->active(), $connections->total()));
    }

    public function exception(Throwable $e): void
    {
        $type = match (true) {
            $e instanceof SignalInterrupt => throw $e,
            $e instanceof SocketException => 'SOCKET',
            $e instanceof ServerRuntimeException => 'SERVER',
            $e instanceof ServerFatalRuntimeException => 'FATAL SERVER',
            default => 'EXTERNAL',
        };

        if (!$this->output->isDebug() && !$this->output->isQuiet()) {
            $this->output->block(sprintf('%s %s%s', $e::class, PHP_EOL, $e->getMessage()), "{$type}} EXCEPTION", 'fg=white;bg=red', ' ', true);
        } elseif ($this->output->isDebug()) {
            $this->output->block([
                '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~',
                '[' . $type . 'EXCEPTION] ' . $e::class,
                $e->getMessage(),
                preg_replace('#' . \dirname(__DIR__, 3) . '/#', '', $e->getTraceAsString()),
                '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~',
            ], null, 'fg=white;bg=red', ' ', true);
        }
        $this->logger->error("[{$type} EXCEPTION]: " . $e->getMessage(), [$e]);
    }

    public function alert(string|Stringable|iterable $message): void
    {
        $this->write($message);
    }

    public function warning(string|Stringable|iterable $message): void
    {
        if (!$this->output->isQuiet()) {
            $this->write($message);
        }
    }

    public function notice(string|Stringable|iterable $message): void
    {
        if ($this->output->isVerbose()) {
            $this->write($message);
        }
    }

    public function info(string|Stringable|iterable $message): void
    {
        if ($this->output->isVeryVerbose()) {
            $this->write($message);
        }
    }

    public function debug(string|Stringable|iterable $message): void
    {
        if ($this->output->isDebug()) {
            $this->write($message);
        }
    }

    private function write(string|Stringable|iterable $messages): void
    {
        $messages = is_iterable($messages) ? $messages : [$messages];
        foreach ($messages as $message) {
            $this->output->writeln("{$message}");
        }
    }
}
