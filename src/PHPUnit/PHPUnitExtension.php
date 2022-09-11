<?php

namespace Camelot\SmtpDevServer\PHPUnit;

use Camelot\SmtpDevServer\Server;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

class PHPUnitExtension implements BeforeFirstTestHook, AfterLastTestHook
{
    private Server $server;

    public function executeBeforeFirstTest(): void
    {
        $this->server = new Server();
        $this->server->start();
    }

    public function executeAfterLastTest(): void
    {
        $this->server->stop();
    }
}
