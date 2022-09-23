<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use const SO_LINGER;
use const SOL_SOCKET;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Exception\ServerFatalRuntimeException;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Output\ServerOutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

trait SocketTrait
{
    private ?\Socket $socket = null;
    private int $bufferSize = 2048;
    private ?string $localName = null;
    private ?Stopwatch $stopwatch = null;
    private ?ServerOutputInterface $output = null;
    private Bytes $bytes;

    public function id(): string
    {
        return spl_object_hash($this);
    }

    public function name(): string
    {
        return $this->localName();
    }

    public function close(Shutdown $shutdown): void
    {
        if ($shutdown->isImmediate() && $this->socket === null) {
            return;
        }
        if ($this->socket === null) {
            throw new ServerRuntimeException('Socket already closed.');
        }
        try {
            if ($shutdown->isImmediate()) {
                $this->blocking();
                self::call('socket_set_option', $this->socket, SOL_SOCKET, SO_LINGER, ['l_linger' => 0, 'l_onoff' => 1]);
                self::call('socket_shutdown', $this->socket, 2);
            }
        } finally {
            try {
                @socket_close($this->socket);
            } catch (Throwable $e) {
                throw new SocketException('socket_close', $this->socket, $e); // @codeCoverageIgnore
            } finally {
                $this->socket = null;
                $this->stopwatch->stop(spl_object_hash($this));
            }
        }

        $this->bytes()->close();
    }

    public function closed(): bool
    {
        return $this->socket === null;
    }

    public function localName(): string
    {
        if ($this->localName === null) {
            if (!@socket_getsockname($this->socket, $address, $port)) {
                throw new SocketException('socket_getsockname', $this->socket); // @codeCoverageIgnore
            }
            $this->localName = "{$address}:{$port}";
        }

        return $this->localName;
    }

    /** @codeCoverageIgnore */
    public function stats(): Stats
    {
        return Stats::create($this->stopwatch->getEvent(spl_object_hash($this)));
    }

    /** @codeCoverageIgnore */
    public function setOutput(ServerOutputInterface $output): void
    {
        $this->output = $output;
    }

    public function socket(): ?\Socket
    {
        return $this->socket;
    }

    public function matches(\Socket|iterable $sockets): bool
    {
        $this->assert(__METHOD__);

        $sockets = $sockets instanceof \Socket ? [$sockets] : $sockets;
        foreach ($sockets as $socket) {
            if ($socket === $this->socket) {
                return true;
            }
        }

        return false;
    }

    public function bytes(): Bytes
    {
        return $this->bytes;
    }

    private function blocking(): void
    {
        self::call('socket_set_block', $this->socket);
    }

    private function nonblocking(): void
    {
        self::call('socket_set_nonblock', $this->socket);
    }

    private function assert(string $method): void
    {
        if ($this->socket === null) {
            match (true) {
                $this instanceof RemoteSocketInterface => throw new ServerRuntimeException(sprintf('Invalid call to %s(), socket is closed.', $method)),
                default => throw new ServerFatalRuntimeException(sprintf('Invalid call to %s(), missing socket. It is either closed, or listen() was not called.', $method)),
            };
        }
    }

    private static function call(string $function, ...$args): mixed
    {
        $result = @($function)(...$args);
        if ($result === false) {
            $socket = null;
            foreach ($args as $arg) {
                if ($arg instanceof \Socket) {
                    $socket = $arg;
                    break;
                }
            }
            throw new SocketException($function, $socket);
        }

        return $result;
    }
}
