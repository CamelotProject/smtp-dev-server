<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Enum\SmtpReply;
use Psr\Log\LoggerInterface;

use function fclose;
use function feof;
use function fwrite;
use function stream_socket_get_name;
use const PHP_EOL;

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

    protected function respond(int|SmtpReply $code, string $delim, ?string $message = null): void
    {
        $code = $code instanceof SmtpReply ? $code->value : $code;
        $this->logger?->debug('Transmitting', ['socket' => (int) $this->socket, 'data' => $message]);
        fwrite($this->socket, "{$code}{$delim}{$message}" . PHP_EOL);
    }
}
