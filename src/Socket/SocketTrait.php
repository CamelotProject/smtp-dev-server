<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Psr\Log\LoggerInterface;

trait SocketTrait
{
    protected string $hostname;
    /** @var null|resource */
    protected $socket;
    protected ?LoggerInterface $logger;

    public function __construct(string $hostname, $socket, LoggerInterface $logger = null)
    {
        $this->hostname = $hostname;
        $this->socket = $socket;
        $this->logger = $logger;

        $this->init();
    }

    public function id(): int
    {
        return (int) $this->socket;
    }

    public function name(): ?string
    {
        return stream_socket_get_name($this->socket, true) ?? null;
    }

    public function open(): void
    {
        $this->logger?->info('Opening socket ' . (int) $this->socket);
    }

    public function write(null|string|\Stringable $response): void
    {
        fwrite($this->socket, (string) $response);
    }

    public function close(): void
    {
        $this->logger?->info('Closing socket ' . (int) $this->socket);
        fclose($this->socket);
        $this->socket = null;
    }

    public function isEOF(): bool
    {
        return !$this->socket || feof($this->socket);
    }

    /** @return null|resource */
    public function socket()
    {
        return $this->socket;
    }

    abstract protected function init(): void;
}
