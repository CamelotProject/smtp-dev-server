<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Event\TransactionEventInterface;

interface ClientSocketInterface extends SocketInterface
{
    /** Name of the socket */
    public function name(): ?string;

    /** Is the socket still open */
    public function isOpen(): bool;

    /** Read data from the socket */
    public function read(): ?TransactionEventInterface;

    /** Write data to the socket */
    public function write(null|string|\Stringable $response): void;

//    /** Messages received during connection. */
//    public function requests(): iterable;
}
