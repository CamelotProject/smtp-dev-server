<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Event\HttpEvent;
use Camelot\SmtpDevServer\Event\TransactionEventInterface;

final class HttpSocket implements ClientSocketInterface
{
    use SocketTrait;
    use ClientSocketTrait;

    public function isOpen(): bool
    {
        return (bool) $this->socket;
    }

    public function open(): void
    {
        $this->acceptSocket();
    }

    public function read(): TransactionEventInterface
    {
        return new HttpEvent($this->hostname, $this, $this->readSocket());
    }

    protected function init(): void
    {
    }
}
