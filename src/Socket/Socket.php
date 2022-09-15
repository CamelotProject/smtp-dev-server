<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Psr\Log\LoggerInterface;

class Socket implements SocketInterface
{
    use SocketTrait;

    protected function init(): void
    {
        $this->logger?->info('Opened socket ' . (int) $this->socket);
    }
}
