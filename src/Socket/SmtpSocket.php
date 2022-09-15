<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use const PHP_EOL;

use Camelot\SmtpDevServer\Enum\SmtpReply;
use Camelot\SmtpDevServer\Event\TransactionEventInterface;
use Camelot\SmtpDevServer\Event\SmtpEvent;
use DateTimeImmutable;

final class SmtpSocket implements ClientSocketInterface
{
    use ClientSocketTrait;
    use SocketTrait;

    public function isOpen(): bool
    {
        return (bool) $this->socket;
    }

    public function open(): void
    {
        $this->acceptSocket();
        $this->ready();
    }

    public function read(): TransactionEventInterface
    {
        return new SmtpEvent($this->hostname, $this, $this->readSocket());
    }

    protected function init(): void
    {
    }

    private function ready(): void
    {
        $this->write(SmtpReply::ServiceReady->value . ' ' . $this->hostname . ' ready at ' . (new DateTimeImmutable('now'))->format('r') . PHP_EOL);
    }
}
