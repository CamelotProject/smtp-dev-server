<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Fixtures;

use const PHP_EOL;
use const SIGTERM;

use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

final class MockServer
{
    private const CODE = <<<'EOF'
        <?php
        use Camelot\SmtpDevServer\Output\ServerOutput;
        use Camelot\SmtpDevServer\DependencyInjection\Kernel;
        use Camelot\SmtpDevServer\Enum\Scheme;
        use Camelot\SmtpDevServer\Server;
        use Camelot\SmtpDevServer\Socket\AddressInfo;
        use Camelot\SmtpDevServer\Socket\Timeout;
        use Psr\Log\NullLogger;
        use Symfony\Component\Console\Output\ConsoleOutput;
        require_once __DIR__ . '/config/bootstrap.php';
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $server = $kernel->getContainer()->get('server.smtp');
        $server->start(new AddressInfo(Scheme::SMTP, '%HOSTNAME%', %PORT%), new Timeout(0, 250), new ServerOutput(new ConsoleOutput(), new NullLogger()));
        $server->listen();
        EOF;

    private Process $process;

    public function __construct(string $host = 'localhost', int $port = 5252)
    {
        $this->process = new PhpProcess(str_replace(['%HOSTNAME%', '%PORT%'], [$host, $port], self::CODE), \dirname(__DIR__, 2));
    }

    public static function create(string $host = 'localhost', int $port = 5252): self
    {
        return new self($host, $port);
    }

    public function start(): void
    {
        $this->process->start();
        usleep(200000);
        if (!$this->process->isRunning()) {
            throw new \RuntimeException(sprintf('Unable to start server:%s%s%s%s', PHP_EOL, $this->process->getOutput(), PHP_EOL, $this->process->getErrorOutput()));
        }
    }

    public function stop(): void
    {
        $remainingTries = 100;
        while ($this->process->isRunning()) {
            if (--$remainingTries <= 0) {
                throw new \RuntimeException(sprintf('Unable to stop server:%s%s%s%s', PHP_EOL, $this->process->getOutput(), PHP_EOL, $this->process->getErrorOutput()));
            }
            $this->process->signal(SIGTERM);
            usleep(200000);
        }
    }
}
