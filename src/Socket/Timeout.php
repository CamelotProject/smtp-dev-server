<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

final class Timeout
{
    private ?int $seconds;
    private int $microseconds;

    public function __construct(?int $seconds = null, int $microseconds = 0)
    {
        $this->seconds = $seconds;
        $this->microseconds = $microseconds;
    }

    public function getSeconds(): ?int
    {
        return $this->seconds;
    }

    public function getMicroseconds(): int
    {
        return $this->microseconds;
    }
}
