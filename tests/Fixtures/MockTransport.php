<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Fixtures;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class MockTransport implements TransportInterface
{
    private TransportInterface $transport;
    private array $sentMessages = [];

    public function __construct(int $port = 5252)
    {
        $this->transport = Transport::fromDsn('smtp://localhost:' . $port);
    }

    public function __toString(): string
    {
        return (string) $this->transport;
    }

    public function send(RawMessage|Email $message, Envelope $envelope = null): ?SentMessage
    {
        return $this->sentMessages[] = $this->transport->send($message, $envelope);
    }

    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }
}
