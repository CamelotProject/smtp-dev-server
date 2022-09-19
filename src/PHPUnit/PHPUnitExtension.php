<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\PHPUnit;

use Camelot\SmtpDevServer\Server;
use Camelot\SmtpDevServer\Socket\ClientFactory;
use Camelot\SmtpDevServer\Socket\SmtpSocket;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Symfony\Component\Console\Output\NullOutput;

class PHPUnitExtension implements BeforeFirstTestHook, AfterLastTestHook
{
    private Server $server;

    public function executeBeforeFirstTest(): void
    {
        $this->server = new Server();
        $this->server->start(new ClientFactory('localhost', 2525, SmtpSocket::class), new NullOutput());
        $this->server->listen();
    }

    public function executeAfterLastTest(): void
    {
        $this->server->stop();
    }
}
