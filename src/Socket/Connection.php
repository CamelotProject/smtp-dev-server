<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use const PHP_BINARY_READ;
use const SOCKET_EWOULDBLOCK;

use Camelot\SmtpDevServer\Exception\SocketException;
use Stringable;
use Symfony\Component\Stopwatch\Stopwatch;

final class Connection implements RemoteSocketInterface
{
    use SocketTrait;

    private ?string $remoteName = null;

    private function __construct(\Socket $socket)
    {
        $this->socket = $socket;
        $this->bytes = new Bytes();
        $this->stopwatch = new Stopwatch(true);
        $this->stopwatch->start(spl_object_hash($this));

        $this->nonblocking();
        $this->localName();
        $this->remoteName();
    }

    public static function create(\Socket $server): self
    {
        return new self(self::call('socket_accept', $server));
    }

    public function id(): string
    {
        return $this->remoteName();
    }

    public function name(): string
    {
        return $this->remoteName();
    }

    /** @codeCoverageIgnore */
    public function open(): void
    {
        $this->output->connection($this);
    }

    public function read(): ?Buffer
    {
        $this->assert(__METHOD__);

        $buffer = null;
        while (true) {
            try {
                $read = self::call('socket_read', $this->socket, $this->bufferSize, PHP_BINARY_READ);
            } catch (SocketException $e) {
                if ($e->getCode() === SOCKET_EWOULDBLOCK) {
                    break;
                }
                throw $e;
            }
            if ($read === '') {
                dump('Breaking on empty string buffer');
                break;
            }
            if ($read === "\r\n") {
                break;
            }
            $buffer .= $read;
            $this->bytes->addReceived(\strlen("{$read}"));
        }

        return new Buffer($buffer);
    }

    public function write(null|string|Stringable $response): int
    {
        $this->assert(__METHOD__);

        $string = $remaining = "{$response}";
        $total = \strlen($string);
        $bytes = 0;

        while ($bytes < $total) {
            $retries = 0;
            while ($retries < 20) { // 5 seconds
                try {
                    $written = self::call('socket_write', $this->socket, $remaining);
                    $this->bytes->addSent($written);
                    $remaining = substr($string, $written + 1);
                    $bytes += $written;
                } catch (SocketException $e) {
                    if ($e->getCode() === SOCKET_EAGAIN) {
                        usleep(250000);
                        ++$retries;
                        continue;
                    }
                    throw $e;
                }

                break;
            }
        }

        return $bytes;
    }

    public function remoteName(): string
    {
        if ($this->remoteName === null) {
            if (!@socket_getpeername($this->socket, $address, $port)) {
                throw new SocketException('socket_getpeername', $this->socket); // @codeCoverageIgnore
            }
            $this->remoteName = "{$address}:{$port}";
        }

        return $this->remoteName;
    }
}
