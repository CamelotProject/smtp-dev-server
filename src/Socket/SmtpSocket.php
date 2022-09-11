<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Enum\SmtpReply;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function fgets;
use function preg_match;
use function stream_socket_accept;
use function trim;

final class SmtpSocket implements SocketInterface
{
    use SocketTrait;

    private bool $receivingData = false;
    private int $messageCount = 0;

    public function __construct(string $hostname, $socket, LoggerInterface $logger = null)
    {
        $this->hostname = $hostname;
        $this->socket = $socket;
        $this->logger = $logger;
    }

    public function open(): void
    {
        $this->socket = stream_socket_accept($this->socket);
        $this->ready();
        $this->logger?->info('Opened client socket ' . (int) $this->socket);
    }

    public function read(): string
    {
        $message = (string) fgets($this->socket);
        $this->logger?->debug('Receiving', ['socket' => (int) $this->socket, 'data' => $message]);

        match (1) {
            preg_match('/^HELO\s+\[?([\d\w.-]+)]?/', $message, $matches) => $this->hello($matches[1]),
            preg_match('/^EHLO\s+\[?([\d\w.-]+)]?/', $message, $matches) => $this->eHello($matches[1]),
            preg_match('/^MAIL FROM:\s*(.+)/', $message, $matches),
            preg_match('/^RCPT TO:\s*(.+)/', $message, $matches),
            preg_match('/^NOOP\s*\R/', $message, $matches) => $this->ok(),
            preg_match('/^DATA\s*\R/', $message, $matches) => $this->data(),
            preg_match('/^QUIT\s*\R/', $message, $matches) => $this->quit(),
            preg_match('/^HELP\s*\R/i', $message, $matches) => $this->help(),
            default => $this->dataStream($message),
        };

        return $message;
    }

    private function ready(): void
    {
        $this->respond(SmtpReply::ServiceReady, ' ', $this->hostname . ' ready at ' . (new DateTimeImmutable('now'))->format('r'));
    }

    private function hello($callingHost): void
    {
        $this->respond(SmtpReply::RequestedMailActionOK, ' ', $this->hostname . ' Hello ' . $callingHost . ' [' . $this->name() . ']');
    }

    private function eHello($callingHost): void
    {
        $this->respond(SmtpReply::RequestedMailActionOK, '-', $this->hostname . ' Hello ' . $callingHost . ' [' . $this->name() . ']');
        $this->respond(SmtpReply::RequestedMailActionOK, '-', 'SIZE 1000000');
        $this->respond(SmtpReply::RequestedMailActionOK, ' ', 'AUTH PLAIN');
    }

    private function data(): void
    {
        if ($this->receivingData) {
            $this->syntaxError();

            return;
        }

        $this->respond(SmtpReply::StartMailInput, ' ', 'Send message content; end with <CRLF>.<CRLF>');
        $this->receivingData = true;
    }

    private function dataStream(?string $data): void
    {
        if (!$this->receivingData) {
            if (trim($data) !== '') {
                $this->syntaxError($data);
            }

            return;
        }

        if (trim($data) === '.') {
            $this->receivingData = false;
            $this->ok('message accepted for delivery: queued as ' . ++$this->messageCount);
        }
    }

    private function quit(): void
    {
        $this->respond(SmtpReply::ServiceClosing, ' ', 'So long, and thanks for all the messages');
        $this->close();
    }

    private function ok(string $message = null): void
    {
        $this->respond(SmtpReply::RequestedMailActionOK, ' ', trim('OK ' . $message));
    }

    private function help(): void
    {
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'Available commands');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'HELP - This is what you get');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'HELO - Start conversation wth a HELO');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'EHLO - Start conversation wth a EHLO');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'MAIL FROM:<sender@domain.tld>');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'RCPT TO:<recipient@domain.tld>');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'DATA');
        $this->respond(SmtpReply::SystemStatusHelp, '-', 'QUIT - Terminate the connection');
        $this->respond(SmtpReply::SystemStatusHelp, ' ', '');
    }

    private function syntaxError(string $data = null): void
    {
        $message = 'Syntax error, command unrecognized: ' . $data;
        $this->logger?->warning($message);
        $this->respond(SmtpReply::SyntaxErrorCommandUnrecognized, ' ', $message);
    }
}
