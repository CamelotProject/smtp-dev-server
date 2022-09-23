<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Output\ServerOutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Traversable;

final class Connections
{
    /** @var Connection[] */
    private array $connections = [];
    private ServerOutputInterface $output;
    private Bytes $bytes;
    private int $total = 0;

    public function __construct(ServerOutputInterface $reporters, Bytes $bytes)
    {
        $this->output = $reporters;
        $this->bytes = $bytes;
    }

    /** @codeCoverageIgnore  */
    public function __destruct()
    {
        if (\count($this->connections) === 0) {
            return;
        }

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
        $output->warning(sprintf('%s connections active. Attempting shutdown.', \count($this->connections)));
        $this->close(Shutdown::Immediate);

        if (\count($this->connections) > 0) {
            $output->error(sprintf('Shutdown failed with %s connections still active:', \count($this->connections)));
            $output->listing(array_keys($this->connections));
            $output->newLine();
        }
    }

    public function add(Connection $connection): void
    {
        $connection->setOutput($this->output);
        $connection->open();
        $this->connections[$connection->id()] = $connection;
        ++$this->total;
        $this->gc();
    }

    public function has(int|string|Connection $connection): bool
    {
        $id = $connection instanceof Connection ? $connection->id() : $connection;

        return isset($this->connections[$id]);
    }

    public function get(int|string $id): Connection
    {
        if (!$this->has($id)) {
            throw new ServerRuntimeException(sprintf('Connection with the ID of "%s" does not exist.', $id));
        }

        return $this->connections[$id];
    }

    /** Close a connection and remove it from the collection. */
    public function remove(int|string|Connection $connection, Shutdown $shutdown): void
    {
        $id = $connection instanceof Connection ? $connection->id() : $connection;
        if (!$this->has($id)) {
            throw new ServerRuntimeException(sprintf('Connection with the ID of "%s" does not exist.', $id));
        }

        $this->gc();
        if (!$connection->closed()) {
            $connection->close($shutdown);
            $this->gc();
            $this->output->disconnection($connection);
        }
        $this->output->termination($connection);
    }

    /** Forcibly shutdown all known connections. */
    public function close(Shutdown $shutdown): void
    {
        foreach ($this->connections as $connection) {
            $this->remove($connection, $shutdown);
        }
    }

    /** @return \Socket[] */
    public function sockets(): array
    {
        $this->gc();

        return array_map(fn (Connection $c): \Socket => $c->socket(), $this->connections);
    }

    /** @return Connection[] */
    public function matching(\Socket|array $sockets): Traversable
    {
        foreach ($this->connections as $connection) {
            if ($connection->matches($sockets)) {
                yield $connection;
            }
        }
    }

    public function active(): int
    {
        return \count($this->connections);
    }

    public function total(): int
    {
        return $this->total;
    }

    private function gc(): void
    {
        // Do accounting on any closed connections
        array_map(fn (Connection $c) => $this->bytes->merge($c->bytes()), array_filter($this->connections, fn (Connection $c): bool => $c->closed()));

        $this->connections = array_filter($this->connections, fn (Connection $c): bool => !$c->closed());
    }
}
