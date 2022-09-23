<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Output\ServerOutputInterface;

interface SocketInterface
{
    /**
     * The object handle of the socket.
     *
     * @throws SocketException if the socket is not open, or is closed
     */
    public function id(): string;

    /**
     * The local name for listeners, and remote name for connections.
     *
     * @throws SocketException if the socket is not open, or is closed
     */
    public function name(): string;

    /**
     * Open the socket.
     *
     * @throws SocketException
     */
    public function open(): void;

    /**
     * Close the socket.
     *
     * @throws SocketException
     */
    public function close(Shutdown $shutdown): void;

    /** Is the connection closed. */
    public function closed(): bool;

    /**
     * Get the connection's local name.
     *
     * @throws SocketException
     */
    public function localName(): string;

    /** Bytes sent and received on this socket, or is connections to listening sockets. */
    public function bytes(): Bytes;

    /** Report on connection stats to one or more logger/output. */
    public function stats(): Stats;

    public function setOutput(ServerOutputInterface $output): void;

    /** @internal */
    public function socket(): ?\Socket;

    /** @internal */
    public function matches(\Socket|iterable $sockets): bool;
}
