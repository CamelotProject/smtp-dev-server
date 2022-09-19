<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Socket\ClientSocketInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class HttpEvent extends Event implements TransactionEventInterface
{
    private string $hostname;
    private ClientSocketInterface $client;
    private string $message;
    private null|string|\Stringable $response;
    private object $controller;

    public function __construct(string $hostname, ClientSocketInterface $client, string $message, \Stringable|string|null $response = null)
    {
        $this->hostname = $hostname;
        $this->client = $client;
        $this->message = $message;
        $this->response = $response;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getClient(): ClientSocketInterface
    {
        return $this->client;
    }

    public function getMessage(): \Stringable|string|null
    {
        return $this->message;
    }

    public function getResponse(): \Stringable|string|null
    {
        return $this->response;
    }

    public function setResponse(\Stringable|string|null $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function stayAlive(): bool
    {
        return false;
    }

    public function getController(): object
    {
        return $this->controller;
    }

    public function setController(object $controller): void
    {
        $this->controller = $controller;
    }
}
