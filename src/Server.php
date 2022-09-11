<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use Camelot\SmtpDevServer\Socket\SmtpSocket;
use Camelot\SmtpDevServer\Socket\Socket;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function array_filter;
use function array_map;
use function fclose;
use function feof;
use function preg_replace;
use function stream_select;

final class Server
{
    private string $host;
    private string $hostname;
    private ?LoggerInterface $logger;

    /** @var null|resource */
    private $socket;
    /** @var SmtpSocket[] */
    private array $sockets = [];

    public function __construct(string $host, int $port = 2525, LoggerInterface $logger = null)
    {
        if (!str_contains($host, '://')) {
            $host = 'tcp://' . $host . ($port ? ':' . $port : '');
        }

        $this->host = $host;
        $this->hostname = preg_replace(['#^\w+://#', '#:\d+$#'], '', $host);
        $this->logger = $logger;
    }

    public function start(): void
    {
        if (!$this->socket = stream_socket_server($this->host, $errno, $errstr)) {
            throw new RuntimeException(sprintf('Server start failed on "%s": ', $this->host) . $errstr . ' ' . $errno);
        }
        $this->sockets[(int) $this->socket] = new Socket($this->hostname, $this->socket, $this->logger);
    }

    public function stop(): void
    {
        fclose($this->socket);

        $this->socket = null;
    }

    /** @param callable $callback A callable with the signature (int $id, string $data): void */
    public function listen(callable $callback): void
    {
        if ($this->socket === null) {
            $this->start();
        }

        foreach ($this->main() as $id => $data) {
            $callback($id, $data);
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    private function main(): iterable
    {
        while (true) {
            $listeners = $this->listeners();
            foreach ($listeners as $id => $socket) {
                if ($this->socket === $socket) {
                    $client = $this->createClient();
                    $listeners[$client->id()] = $client->socket();

                    yield $client->id() => '--- [CONNECT] '. $client->name();

                    continue;
                }
                $client = $this->sockets[$id];
                $clientId = $client->id();
                $name = $client->name();

                if (feof($socket)) {
                    unset($this->sockets[$id]);
                    $client->close();
                } else {
                    yield $clientId => $client->read();
                }

                if (!$client->socket()) {
                    yield $clientId => '--- [DISCONNECT] ' . $name;
                }
            }
        }
    }

    private function listeners(): array
    {
        $write = [];
        $this->sockets = array_filter($this->sockets, fn ($c) => !$c->isEOF());
        $listeners = array_map(fn ($c) => $c->socket(), $this->sockets);
        stream_select($listeners, $write, $write, null);

        return $listeners;
    }

    private function createClient(): SmtpSocket
    {
        $client = new SmtpSocket($this->hostname, $this->socket, $this->logger);
        $client->open();

        $this->sockets[$client->id()] = $client;

        return $client;
    }
}
