<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Socket\ClientSocketInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class SmtpEvent extends Event implements TransactionEventInterface
{
    private string $hostname;
    private ClientSocketInterface $client;
    private string $message;
    private null|string|\Stringable $response;

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
        return true;
    }
}
