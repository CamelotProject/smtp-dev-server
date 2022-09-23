<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use const AF_INET;
use const SOCK_STREAM;
use const SOL_TCP;
use const SOMAXCONN;

use Symfony\Component\Stopwatch\Stopwatch;

final class Listener implements SocketInterface
{
    use SocketTrait;

    private string $address;
    private int $port;
    private int $backlog = SOMAXCONN;

    public function __construct(string $address, int $port)
    {
        $this->address = $address;
        $this->port = $port;
        $this->bytes = new Bytes();
        $this->stopwatch = new Stopwatch(true);
    }

    public function open(): void
    {
        $socket = self::call('socket_create', AF_INET, SOCK_STREAM, SOL_TCP);

        self::call('socket_bind', $socket, $this->address, $this->port);

        $this->socket = $socket;
        $this->localName();
        $this->stopwatch->start(spl_object_hash($this));
    }

    public function listen(): void
    {
        $this->assert(__METHOD__);

        self::call('socket_listen', $this->socket, $this->backlog);
    }

    public function accept(): Connection
    {
        return Connection::create($this->socket);
    }
}
