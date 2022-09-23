<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Enum\Scheme;

final class AddressInfo
{
    private Scheme $scheme;
    private string $address;
    private int $port;

    public function __construct(Scheme $scheme, string $address, int $port)
    {
        $this->scheme = $scheme;
        $this->address = $address;
        $this->port = $port;
    }

    public function formatted(): string
    {
        return "{$this->scheme->value}://{$this->address}:{$this->port}";
    }

    public function getScheme(): Scheme
    {
        return $this->scheme;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
