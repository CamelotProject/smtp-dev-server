<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Exception;

use const AF_INET6;
use const SOCK_STREAM;
use const SOL_TCP;

use Camelot\SmtpDevServer\Exception\SocketException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Exception\SocketException
 *
 * @internal
 */
final class SocketExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $socket = @socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        try {
            $this->setupSocket($socket);
            $exception = new SocketException('socket_bind', $socket);
        } finally {
            @socket_close($socket);
        }

        static::assertSame('socket_bind() failed. (111) Connection refused', $exception->getMessage());
    }

    public function testConstructWithIterableSockets(): void
    {
        $socket = @socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        try {
            $this->setupSocket($socket);
            $exception = new SocketException('socket_bind', [$socket]);
        } finally {
            @socket_close($socket);
        }

        static::assertSame('socket_bind() failed. (111) Connection refused', $exception->getMessage());
    }

    public function testConstructWithEmptySocket(): void
    {
        $socket = @socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        try {
            $this->setupSocket($socket);
            $exception = new SocketException('socket_bind', null);
        } finally {
            @socket_close($socket);
        }

        static::assertSame('socket_bind() failed. (111) Connection refused', $exception->getMessage());
    }

    private function setupSocket(\Socket $socket): \Socket
    {
        static::assertNotFalse($socket);
        $bind = @socket_bind($socket, '0.0.0.0', 0);
        static::assertNotFalse($bind);
        $connect = @socket_connect($socket, '127.0.0.1', 80);
        static::assertFalse($connect);

        return $socket;
    }
}
