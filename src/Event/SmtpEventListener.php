<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use const PHP_EOL;

use Camelot\SmtpDevServer\Enum\SmtpReply;
use Camelot\SmtpDevServer\Request\SmtpRequest;
use Camelot\SmtpDevServer\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function preg_match;
use function trim;

final class SmtpEventListener implements EventSubscriberInterface
{
    private StorageInterface $storage;
    private ?LoggerInterface $logger;
    /** @var SmtpRequest[] */
    private array $pending = [];
    private array $receivingData = [];
    private int $messageCount = 0;

    public function __construct(StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SmtpEvent::class => 'handleMessage',
        ];
    }

    public function handleMessage(SmtpEvent $event): void
    {
        $message = $event->getMessage();

        match (1) {
            preg_match('/^HELO\s+\[?([\d\w.-]+)]?\R/', $message, $matches) => $this->hello($event, $matches[1]),
            preg_match('/^EHLO\s+\[?([\d\w.-]+)]?\R/', $message, $matches) => $this->eHello($event, $matches[1]),
            preg_match('/^MAIL FROM:\s*(.+)\R/', $message, $matches) => $this->mailFrom($event, $matches[1]),
            preg_match('/^RCPT TO:\s*(.+)\R/', $message, $matches) => $this->rcptTo($event, $matches[1]),
            preg_match('/^NOOP\s*\R/', $message, $matches) => $this->ok($event),
            preg_match('/^DATA\s*\R/', $message, $matches) => $this->data($event),
            preg_match('/^QUIT\s*\R/', $message, $matches) => $this->quit($event),
            preg_match('/^HELP\s*\R/i', $message, $matches) => $this->help($event),
            default => $this->dataStream($event, $message),
        };
    }

    private function hello(SmtpEvent $event, ?string $callingHost): void
    {
        $this->respond($event, SmtpReply::RequestedMailActionOK, ' ', $event->getHostname() . ' Hello ' . $callingHost . ' [' . $event->getClient()->name() . ']');
    }

    private function eHello(SmtpEvent $event, ?string $callingHost): void
    {
        $this->respond($event, SmtpReply::RequestedMailActionOK, '-', $event->getHostname() . ' Hello ' . $callingHost . ' [' . $event->getClient()->name() . ']');
        $this->respond($event, SmtpReply::RequestedMailActionOK, '-', 'SIZE 1000000');
        $this->respond($event, SmtpReply::RequestedMailActionOK, ' ', 'AUTH PLAIN');
    }

    private function mailFrom(SmtpEvent $event, ?string $address): void
    {
        $request = $this->request($event->getClient()->id());
        $request->headers->set('MAIL FROM', $address);
        $this->ok($event);
    }

    private function rcptTo(SmtpEvent $event, ?string $addresses): void
    {
        $request = $this->request($event->getClient()->id());
        $request->headers->set('RCPT TO', $addresses);
        $this->ok($event);
    }

    private function data(SmtpEvent $event): void
    {
        $id = $event->getClient()->id();
        if ($this->receivingData[$id] ?? false) {
            $this->syntaxError($event);

            return;
        }

        $this->respond($event, SmtpReply::StartMailInput, ' ', 'Send message content; end with <CRLF>.<CRLF>');
        $this->receivingData[$id] = true;
    }

    private function dataStream(SmtpEvent $event, ?string $data): void
    {
        if (!$this->receivingData) {
            if (trim($data) !== '') {
                $this->syntaxError($event, $data);
            }

            return;
        }
        $id = $event->getClient()->id();
        $request = $this->request($id);

        if (trim($data) === '.') {
            $this->receivingData[$id] = false;
            unset($this->pending[$id]);
            $this->storage->add($request->getContent());

            $this->ok($event, 'message accepted for delivery: queued as ' . $this->messageCount);

            return;
        }
        $request->setContent($request->getContent() . $data);
    }

    private function quit(SmtpEvent $event): void
    {
        $this->respond($event, SmtpReply::ServiceClosing, ' ', 'So long, and thanks for all the messages');
        $event->getClient()->close();
    }

    private function ok(SmtpEvent $event, string $message = null): void
    {
        $this->respond($event, SmtpReply::RequestedMailActionOK, ' ', trim('OK ' . $message));
    }

    private function help(SmtpEvent $event): void
    {
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'Available commands');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'HELP - This is what you get');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'HELO - Start conversation wth a HELO');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'EHLO - Start conversation wth a EHLO');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'MAIL FROM:<sender@domain.tld>');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'RCPT TO:<recipient@domain.tld>');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'DATA');
        $this->respond($event, SmtpReply::SystemStatusHelp, '-', 'QUIT - Terminate the connection');
        $this->respond($event, SmtpReply::SystemStatusHelp, ' ', '');
    }

    private function syntaxError(SmtpEvent $event, string $data = null): void
    {
        $message = 'Syntax error, command unrecognized: ' . $data;
        $this->logger?->warning($message);
        $this->respond($event, SmtpReply::SyntaxErrorCommandUnrecognized, ' ', $message);
    }

    private function request(int $id): SmtpRequest
    {
        if ($this->pending[$id] ?? false) {
            return $this->pending[$id];
        }

        return $this->pending[$id] = new SmtpRequest();
    }

    private function respond(SmtpEvent $event, int|SmtpReply $code, string $delim, ?string $message = null): void
    {
        $code = $code instanceof SmtpReply ? $code->value : $code;
        $event->setResponse("{$code}{$delim}{$message}" . PHP_EOL);
    }
}
