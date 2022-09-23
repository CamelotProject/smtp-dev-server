<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Socket\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\Connection
 * @covers \Camelot\SmtpDevServer\Socket\SocketTrait
 *
 * @internal
 */
final class ConnectionTest extends TestCase
{
    use ListenerClientTrait;

    public function testRead(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());

                $written = @socket_write($socket, 'squirrel');
                $read = $connection->read();

                static::assertSame(8, $written);
                static::assertSame(8, \strlen("{$read}"));
                static::assertSame('squirrel', "{$read}");
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testRemoteName(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                @socket_getsockname($socket, $address, $port);

                static::assertSame("{$address}:{$port}", $connection->remoteName());
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testBytesReceivedSent(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());

                @socket_write($socket, 'squirrel'); // 8 bytes
                (string) $connection->read();
                $connection->write('nuts'); // 4 bytes
                $this->shutdownSocket($socket);

                static::assertSame(8, $connection->bytes()->received());
                static::assertSame(4, $connection->bytes()->sent());
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testId(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                @socket_getsockname($socket, $address, $port);
                static::assertSame("{$address}:{$port}", $connection->id());
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testName(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                @socket_getsockname($socket, $address, $port);
                static::assertSame("{$address}:{$port}", $connection->name());
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testClose(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                static::assertFalse($connection->closed());
                $connection->close(Shutdown::Immediate);
                static::assertTrue($connection->closed());
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testLocalName(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                static::assertSame('127.0.0.1:5252', $connection->localName());
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testSocket(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                static::assertInstanceOf(\Socket::class, $connection->socket());
                @socket_getsockname($socket, $address1, $port1);
                @socket_getpeername($connection->socket(), $address2, $port2);
                static::assertSame("{$address1}:{$port1}", "{$address2}:{$port2}");
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testMatches(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = Connection::create($listener->socket());
                static::assertTrue($connection->matches($connection->socket()));
                static::assertFalse($connection->matches($listener->socket()));
            } finally {
                @$this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }
}
