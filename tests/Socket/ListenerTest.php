<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Socket\Connection;
use Camelot\SmtpDevServer\Socket\Listener;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\Listener
 * @covers \Camelot\SmtpDevServer\Socket\SocketTrait
 *
 * @internal
 */
final class ListenerTest extends TestCase
{
    use ListenerClientTrait;

    public function testOpen(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertSame('127.0.0.1:5252', $listener->localName());
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testOpenBindFails(): void
    {
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('socket_bind() failed. (13) Permission denied');

        $listener = $this->getListener('127.0.0.1', 1);

        try {
            $listener->open();
            $listener->listen();
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testListenBeforeOpen(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid call to ' . Listener::class . '::listen(), missing socket');

        $listener = $this->getListener();

        try {
            $listener->listen();
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testListen(): void
    {
        $listener = $this->getListener();

        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();
            try {
                static::assertSame('127.0.0.1:5252', $listener->localName());
                @socket_getpeername($socket, $ip, $port);
                static::assertSame('127.0.0.1', $ip);
                static::assertSame(5252, $port);
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testAccept(): void
    {
        $listener = $this->getListener();

        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connection = $listener->accept();
                try {
                    static::assertInstanceOf(Connection::class, $connection);
                    static::assertInstanceOf(\Socket::class, $connection->socket());
                } finally {
                    $connection->close(Shutdown::Immediate);
                }
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testId(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertMatchesRegularExpression('/[0-9a-f]{32}/', $listener->id());
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testName(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertSame('127.0.0.1:5252', $listener->name());
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testClose(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertFalse($listener->closed());
            $listener->close(Shutdown::Normal);
            static::assertTrue($listener->closed());
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testLocalName(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertSame('127.0.0.1:5252', $listener->localName());
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testSocket(): void
    {
        $listener = $this->getListener();
        $listener->open();

        try {
            static::assertInstanceOf(\Socket::class, $listener->socket());
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
                static::assertTrue($listener->matches($listener->socket()));
                static::assertFalse($listener->matches($socket));
            } finally {
                @socket_close($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }
}
