<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use const AF_INET;
use const SO_LINGER;
use const SOCK_STREAM;
use const SOL_SOCKET;
use const SOL_TCP;

use Camelot\SmtpDevServer\Output\ServerOutput;
use Camelot\SmtpDevServer\Socket\Listener;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

trait ListenerClientTrait
{
    private function getListener(string $address = '127.0.0.1', int $port = 5252): Listener
    {
        return new Listener($address, $port);
    }

    private function getServerOutput(): ServerOutput
    {
        return new ServerOutput(new BufferedOutput(), new NullLogger());
    }

    private function createSocket(\Socket $socket = null, string $address = '127.0.0.1', int $port = 5252): \Socket
    {
        $socket ??= @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            static::fail('Can not create client socket');
        }

        $connected = @socket_connect($socket, $address, $port);
        if ($connected === false) {
            $this->shutdownSocket($socket);
            static::fail('Can not connect client socket to listener');
        }

        return $socket;
    }

    private function shutdownSocket(\Socket $socket): void
    {
        try {
            @socket_set_block($socket);
            @socket_set_option($socket, SOL_SOCKET, SO_LINGER, ['l_linger' => 0, 'l_onoff' => 1]);
            @socket_close($socket);
        } catch (Throwable $e) {
        }
    }
}
