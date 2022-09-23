<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Camelot\SmtpDevServer\Event\SocketEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Traversable;

trait NegotiationsTrait
{
    private array $negotiations = [];

    public function __destruct()
    {
        if (\count($this->negotiations) > 0) {
            $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
            $output->error(sprintf('Deconstructing with %s negotiations running:', \count($this->negotiations)));
            $output->listing(array_keys($this->negotiations));
            $output->newLine();
        }
    }

    public function has(string|SocketEvent $connectionId): bool
    {
        $connectionId = $connectionId instanceof SocketEvent ? $connectionId->getConnectionId() : $connectionId;

        return (bool) ($this->negotiations[$connectionId] ?? false);
    }

    public function remove(string|SocketEvent $connectionId): void
    {
        $connectionId = $connectionId instanceof SocketEvent ? $connectionId->getConnectionId() : $connectionId;
        $this->get($connectionId);
        unset($this->negotiations[$connectionId]);
    }

    public function count(): int
    {
        return \count($this->negotiations);
    }

    public function getIterator(): Traversable
    {
        return yield from $this->negotiations;
    }
}
