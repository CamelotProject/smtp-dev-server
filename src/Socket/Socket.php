<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Psr\Log\LoggerInterface;

class Socket implements SocketInterface
{
    use SocketTrait;

    protected string $hostname;
    /** @var null|resource */
    protected $socket;
    protected ?LoggerInterface $logger;

    public function __construct(string $hostname, $socket, LoggerInterface $logger = null)
    {
        $this->hostname = $hostname;
        $this->socket = $socket;
        $this->logger = $logger;
        $this->logger?->info('Opened socket ' . (int) $this->socket);
    }
}
