<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Exception\ServerRuntimeException;

final class Bytes
{
    private int $received = 0;
    private int $sent = 0;
    private bool $closed = false;

    public function closed(): bool
    {
        return $this->closed;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function received(): int
    {
        return $this->received;
    }

    public function sent(): int
    {
        return $this->sent;
    }

    public function addReceived(int $bytes): void
    {
        $this->assert();
        $this->received += $bytes;
    }

    public function addSent(int $bytes): void
    {
        $this->assert();
        $this->sent += $bytes;
    }

    public function merge(self $bytes): void
    {
        $this->assert();
        $this->addReceived($bytes->received());
        $this->addSent($bytes->sent());
    }

    private function assert(): void
    {
        if ($this->closed === true) {
            throw new ServerRuntimeException('An not add bytes after transaction is closed.');
        }
    }
}
