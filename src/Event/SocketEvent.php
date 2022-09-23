<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Enum\Scheme;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use Camelot\SmtpDevServer\Socket\Connection;
use Stringable;
use Symfony\Contracts\EventDispatcher\Event;

final class SocketEvent extends Event implements SocketEventInterface
{
    private AddressInfo $addressInfo;
    private string $connectionId;
    private string $localName;
    private string $remoteName;
    private null|string|Stringable $buffer;
    private null|string|Stringable $response = null;
    private bool $dispatch = false;
    private bool $disconnect = false;

    public function __construct(AddressInfo $addressInfo, Connection $connection, null|string|Stringable $buffer)
    {
        $this->addressInfo = $addressInfo;
        $this->connectionId = $connection->id();
        $this->localName = $connection->localName();
        $this->remoteName = $connection->remoteName();
        $this->buffer = $buffer;
    }

    public function getAddressInfo(): AddressInfo
    {
        return $this->addressInfo;
    }

    public function getScheme(): Scheme
    {
        return $this->addressInfo->getScheme();
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getLocalName(): string
    {
        return $this->localName;
    }

    public function getRemoteName(): string
    {
        return $this->remoteName;
    }

    public function getBuffer(): null|string|Stringable
    {
        return $this->buffer;
    }

    public function getResponse(): null|string|Stringable
    {
        return $this->response;
    }

    public function setResponse(null|string|Stringable $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function dispatch(): void
    {
        $this->dispatch = true;
    }

    public function shouldDispatch(): bool
    {
        return $this->dispatch;
    }

    public function disconnect(): void
    {
        $this->disconnect = true;
    }

    public function shouldDisconnect(): bool
    {
        return $this->disconnect;
    }
}
