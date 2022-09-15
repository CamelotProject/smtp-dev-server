<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Psr\Log\LoggerInterface;

trait ClientSocketTrait
{
    private function acceptSocket(): void
    {
        $this->socket = stream_socket_accept($this->socket);
        $this->logger?->info('Opened client socket ' . (int) $this->socket);
    }

    private function readSocket(): string
    {
        $message = (string) fgets($this->socket);
        $this->logger?->debug('Receiving', ['socket' => (int) $this->socket, 'data' => $message]);

        return $message;
    }
}
