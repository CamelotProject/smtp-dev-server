<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Socket\Bytes;
use Camelot\SmtpDevServer\Socket\Connection;
use Camelot\SmtpDevServer\Socket\Connections;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\Connections
 *
 * @internal
 */
final class ConnectionsTest extends TestCase
{
    use ListenerClientTrait;

    public function testAddHasGet(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connections = new Connections($this->getServerOutput(), new Bytes());
                $connection = Connection::create($listener->socket());

                static::assertFalse($connections->has($connection), 'Connection should not be present in newly constructed Connection. This should not happen.');
                $connections->add($connection);
                static::assertTrue($connections->has($connection), 'Added Connection object not present in collection.');
                static::assertSame($connection, $connections->get($connection->id()), 'Added Connection does not match the one added.');
                $connections->remove($connection, Shutdown::Normal);
                static::assertFalse($connections->has($connection), 'Connection still present in collection after remove()');
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testShutdown(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();

            try {
                $connections = new Connections($this->getServerOutput(), new Bytes());
                $connection = Connection::create($listener->socket());
                $connections->add($connection);

                static::assertFalse($connection->closed(), 'Connection should be open after add()');

                $connections->close(Shutdown::Normal);

                static::assertFalse($connections->has($connection), 'Connection still present after shutdown.');
                static::assertTrue($connection->closed(), 'Connection should be closed during shutdown.');
            } finally {
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testSockets(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();
            $connections = new Connections($this->getServerOutput(), new Bytes());

            try {
                $connection = Connection::create($listener->socket());
                $connections->add($connection);
                $sockets = $connections->sockets();

                static::assertCount(1, $sockets);
                static::assertFalse($connection->closed(), 'Connection should still be open.');
            } finally {
                $connections->close(Shutdown::Immediate);
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testMatching(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();
            $connections = new Connections($this->getServerOutput(), new Bytes());

            try {
                $connection = Connection::create($listener->socket());
                $connections->add($connection);
                $matching = iterator_to_array($connections->matching($connection->socket()));

                static::assertCount(1, $matching);
                static::assertSame($connection, $matching[0]);
            } finally {
                $connections->close(Shutdown::Immediate);
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }

    public function testNotMatching(): void
    {
        $listener = $this->getListener();
        try {
            $listener->open();
            $listener->listen();
            $socket = $this->createSocket();
            $connections = new Connections($this->getServerOutput(), new Bytes());

            try {
                $connection = Connection::create($listener->socket());
                $connections->add($connection);
                $matching = iterator_to_array($connections->matching($socket));

                static::assertCount(0, $matching);
            } finally {
                $connections->close(Shutdown::Immediate);
                $this->shutdownSocket($socket);
            }
        } finally {
            $listener->close(Shutdown::Immediate);
        }
    }
}
