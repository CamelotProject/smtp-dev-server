<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use ArrayObject;
use Camelot\SmtpDevServer\Socket\ClientFactory;
use Camelot\SmtpDevServer\Socket\ClientSocketInterface;
use Camelot\SmtpDevServer\Socket\Socket;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Server
{
    private EventDispatcherInterface $dispatcher;
    private ?LoggerInterface $logger;

    private ?ClientFactory $clientFactory = null;
    private ?string $address = null;
    private ?string $hostname = null;
    private ?OutputInterface $output;

    /** @var null|resource */
    private $socket;
    /** @var ClientSocketInterface[] */
    private array $sockets = [];

    public function __construct(EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function start(ClientFactory $clientFactory, OutputInterface $output): void
    {
        if ($this->clientFactory) {
            throw new \RuntimeException('Server already started.');
        }

        $this->clientFactory = $clientFactory;
        $this->address = $clientFactory->getHostAddress();
        $this->hostname = $clientFactory->getHostname();
        $this->output = $output;

        if (!$this->socket = stream_socket_server($this->address, $errorCode, $errorMessage)) {
            throw new RuntimeException(sprintf('Server start failed on "%s": ', $this->address) . $errorMessage . ' ' . $errorCode);
        }
        $this->sockets[(int) $this->socket] = new Socket($this->hostname, $this->socket, $this->logger);
    }

    public function stop(): void
    {
        if ($this->socket) {
            fclose($this->socket);
        }

        $this->clientFactory = null;
        $this->address = null;
        $this->hostname = null;
        $this->output = null;
        $this->socket = null;
    }

    /** Listen on the server port for new connections. */
    public function listen(): void
    {
        if ($this->socket === null) {
            throw new RuntimeException('Server not started yet.');
        }

        while (true) {
            $listeners = $this->listeners();
            foreach ($listeners as $id => $socket) {
                if ($this->socket === $socket) {
                    $this->accept($socket, $listeners);
                } else {
                    $this->handle($id, $socket);
                }
            }
        }
    }

    /**
     * Accept a client connection and open the socket.
     *
     * @param resource $socket
     */
    private function accept($socket, ArrayObject $listeners): void
    {
        $client = $this->clientFactory->createClient($socket);
        $this->sockets[$client->id()] = $client;
        $listeners[$client->id()] = $client->socket();
        $this->output->writeln("--- [CONNECT] ({$client->id()}) {$client->name()}");
    }

     /**
      * Handle incoming client messages.
      *
      * @param resource $socket
      */
     private function handle(int $id, $socket): void
     {
         $client = $this->sockets[$id];
         $clientId = $client->id();
         $name = $client->name();

         if (feof($socket)) {
             unset($this->sockets[$id]);
             $client->close();
         } else {
             $this->read($client);
         }

         if (!$client->isOpen()) {
             $this->output->writeln("--- [DISCONNECT] ({$clientId}) {$name}");
         }
     }

    private function read(ClientSocketInterface $client): void
    {
        $event = $client->read();
        $clientId = $client->id();

        $this->output->write($event->getMessage());
        $this->dispatcher->dispatch($event);

        $response = $event->getResponse();
        if ($response && $client->isOpen()) {
            $this->logger?->debug('Transmitting', ['socket' => $clientId, 'data' => $response]);

            $client->write($response);

            if (!$event->stayAlive()) {
                $client->close();
            }
        }
    }

    private function listeners(): ArrayObject
    {
        $write = [];
        $this->sockets = array_filter($this->sockets, fn ($c) => !$c->isEOF());
        $listeners = array_map(fn ($c) => $c->socket(), $this->sockets);
        stream_select($listeners, $write, $write, null);

        return new ArrayObject($listeners);
    }
}
