<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Psr\Log\LoggerInterface;

final class ClientFactory
{
    private string $hostname;
    private int $port;
    private string $socketClass;
    private ?LoggerInterface $logger;

    public function __construct(string $hostname, int $port, string $socketClass, ?LoggerInterface $logger = null)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->socketClass = $socketClass;
        $this->logger = $logger;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getHostAddress(): string
    {
        return match ($this->socketClass) {
            HttpSocket::class => 'tcp://' . $this->hostname . ':' . $this->port,
            default => throw new \RuntimeException(sprintf('Unknown client socket class %s', $this->socketClass)),
        };
    }

    public function createClient($socket, LoggerInterface $logger = null): ClientSocketInterface
    {
        $logger ??= $this->logger;
        return match ($this->socketClass) {
            SmtpSocket::class => $this->createSmtpClient($socket, $logger),
            default => throw new \RuntimeException(sprintf('Unknown client socket class %s', $this->socketClass)),
        };
    }

    private function createSmtpClient($socket, ?LoggerInterface $logger): SmtpSocket
    {
        $client = new SmtpSocket($this->hostname, $socket, $logger);
        $client->open();

        return $client;
    }
}
