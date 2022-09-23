<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Enum\SocketAction;
use Camelot\SmtpDevServer\Event\SocketEvent;
use Camelot\SmtpDevServer\Event\SocketEventInterface;
use Camelot\SmtpDevServer\Exception\InternalExceptionInterface;
use Camelot\SmtpDevServer\Exception\ServerFatalRuntimeException;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Exception\SocketException;
use Camelot\SmtpDevServer\Output\ServerOutputInterface;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use Camelot\SmtpDevServer\Socket\Connection;
use Camelot\SmtpDevServer\Socket\Connections;
use Camelot\SmtpDevServer\Socket\Listener;
use Camelot\SmtpDevServer\Socket\Timeout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class Server
{
    private EventDispatcherInterface $dispatcher;
    private ?AddressInfo $addressInfo = null;
    private ?Timeout $timeout = null;
    private ?Listener $listener = null;
    private ?Connections $connections = null;
    private ?ServerOutputInterface $output = null;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __destruct()
    {
        if ($this->running()) {
            $this->connections?->close(Shutdown::Immediate);
            $this->listener->close(Shutdown::Immediate);
        }
    }

    public function id(): ?string
    {
        return $this->listener->id();
    }

    /**
     * Start the server, i.e. create and bind to a listening socket.
     *
     * @throws SocketException if creating or binding of the socket fails
     */
    public function start(AddressInfo $addressInfo, Timeout $timeout, ServerOutputInterface $output): void
    {
        if ($this->listener) {
            throw new ServerRuntimeException('Server already started.');
        }

        $listener = new Listener($addressInfo->getAddress(), $addressInfo->getPort());
        $listener->setOutput($output);

        $listener->open();
        $connections = new Connections($output, $listener->bytes());

        $this->addressInfo = $addressInfo;
        $this->timeout = $timeout;
        $this->listener = $listener;
        $this->connections = $connections;
        $this->output = $output;

        $this->output->startup($addressInfo);
    }

    /**
     * Listen on the server port for new connections.
     *
     * @throws ServerRuntimeException      when the server has not already been started
     * @throws ServerFatalRuntimeException when a fatal runtime exception is throw
     * @throws Throwable                   when an exception occurs that isn't internally mitigated
     */
    public function listen(): void
    {
        if ($this->listener === null) {
            throw new ServerRuntimeException('Server not started yet.');
        }

        $this->listener->listen();
        $this->output->listening($this->addressInfo, $this->listener->id());

        while ($this->listener->closed() !== true) {
            $read = $write = [$this->listener->socket(), ...$this->connections->sockets()];
            if (!$read) {
                continue;
            }

            $select = @socket_select($read, $write, $except, $this->timeout->getSeconds(), $this->timeout->getMicroseconds());
            if ($select === false) {
                throw new SocketException('socket_select', $read); // FIXME is this right? Is this fails we probably want to exit everything
            }

            try {
                $this->connect($read);
            } catch (Throwable $e) {
                $this->output->exception($e);
                throw $e;
            }

            try {
                $this->serve($read);
            } catch (InternalExceptionInterface) {
                continue;
            }
        }
    }

    /** Is the server running */
    public function running(): bool
    {
        return $this->listener && !$this->listener->closed();
    }

    /** Stop the running server, shutdown open listener & connection sockets running in this instance. */
    public function stop(Shutdown $shutdown): void
    {
        $this->output?->shutdown($this->addressInfo, $shutdown);

        $this->connections?->close(Shutdown::Immediate);
        $this->listener?->close(Shutdown::Immediate);

        if ($shutdown->isFinal()) {
            $this->output?->shutdownFinal($this->addressInfo, $this->listener, $this->connections, $shutdown);
            $this->listener = null;
            $this->connections = null;
        }
    }

    /**
     * Accept incoming connections.
     *
     * Exceptions throw here should terminate the server.
     *
     * @throws Throwable
     */
    private function connect(array $read): void
    {
        if ($this->listener->matches($read) === false) {
            return;
        }

        $connection = $this->listener->accept();
        $this->connections->add($connection);

        $event = new SocketEvent($this->addressInfo, $connection, null);
        $this->dispatch($event, SocketAction::connect);
        $this->respond($connection, $event);
    }

    /**
     * Serve active connections.
     *
     * @throws Throwable for all uncaught exceptions making it here, and will cause the connection to be terminated
     */
    private function serve(array $read): void
    {
        foreach ($this->connections->matching($read) as $active) {
            try {
                // Read the incoming client connection and formulate the response.
                $buffer = $active->read();
                $event = new SocketEvent($this->addressInfo, $active, $buffer);
                $this->dispatch($event, SocketAction::buffer);

                // Report on, and send response.
                $this->output->request($buffer, $event);
                $this->respond($active, $event);
            } catch (Throwable $e) {
                $this->output->exception($e);
                $this->terminate($active, Shutdown::Immediate);
                throw $e;
            }
        }
    }

    /** Respond to the transaction request. */
    private function respond(Connection $connection, SocketEventInterface $event): void
    {
        if (!$event->shouldDispatch()) {
            return;
        }

        $response = $event->getResponse();
        if ($response !== null) {
            $bytes = $connection->write("{$response}");
            $this->output->response($response, $bytes, $event);
        }

        if ($event->shouldDisconnect()) {
            $this->terminate($connection, Shutdown::Normal);
        }
    }

    /** Terminate the connection. */
    private function terminate(Connection $connection, Shutdown $shutdown): void
    {
        $event = new SocketEvent($this->addressInfo, $connection, null);
        $this->dispatch($event, SocketAction::disconnect);

        $this->connections->remove($connection, $shutdown);
        $this->output?->stats($connection);
        $this->output?->status($this->listener, $this->connections);
    }

    /** @throws Throwable */
    private function dispatch(SocketEvent $event, SocketAction $action): void
    {
        try {
            $this->output->dispatch($event, $action);
            $this->dispatcher->dispatch($event, $action->name);
        } catch (Throwable $e) {
            $this->output->dispatched($e);
            throw $e;
        }
        $this->output->dispatched();
    }
}
