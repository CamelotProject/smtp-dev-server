<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Socket\AddressInfo;
use Stringable;

interface SocketEventInterface
{
    public function getAddressInfo(): AddressInfo;

    public function getBuffer(): null|string|Stringable;

    public function getResponse(): null|string|Stringable;

    public function setResponse(null|string|Stringable $response): self;

    public function disconnect(): void;

    public function shouldDisconnect(): bool;
}
