<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Exception\SocketException;
use Stringable;

interface RemoteSocketInterface extends SocketInterface
{
    /**
     * Read from the socket.
     *
     * @throws SocketException
     *
     * @return null|string|Stringable the data read from the socket
     */
    public function read(): null|string|Stringable;

    /**
     * Write to the socket.
     *
     * @throws SocketException
     *
     * @return int number of bytes written
     */
    public function write(string $response): int;

    /**
     * Get the connection's remote name.
     *
     * @throws SocketException
     */
    public function remoteName(): string;
}
