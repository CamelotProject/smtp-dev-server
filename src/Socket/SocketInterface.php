<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

interface SocketInterface
{
    /** The process ID of the socket */
    public function id(): int;

    /** Open the socket */
    public function open(): void;

    /** Close the socket */
    public function close(): void;

    /** Is the data transmission finished */
    public function isEOF(): bool;

    /** @return null|resource */
    public function socket();
}
