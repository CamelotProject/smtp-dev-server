<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Generator;
use Stringable;

final class Buffer implements Stringable
{
    private Generator|string $data;

    public function __construct(string|Generator $data)
    {
        $this->data = $data;
    }

    public function __toString(): string
    {
        if (\is_string($this->data)) {
            return $this->data;
        }

        return $this->data = implode('', iterator_to_array($this->data));
    }
}
