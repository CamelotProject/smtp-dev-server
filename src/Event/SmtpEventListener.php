<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Enum\Scheme;
use Camelot\SmtpDevServer\Enum\SmtpResponseCode;
use Camelot\SmtpDevServer\Enum\SocketAction;
use Camelot\SmtpDevServer\Exception\SmtpNegotiationException;
use Camelot\SmtpDevServer\Exception\SmtpSyntaxException;
use Camelot\SmtpDevServer\Helper;
use Camelot\SmtpDevServer\Request\SmtpNegotiation;
use Camelot\SmtpDevServer\Request\SmtpNegotiations;
use Camelot\SmtpDevServer\Response\SmtpResponse;
use Camelot\SmtpDevServer\Response\SmtpResponseFactory;
use Camelot\SmtpDevServer\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SmtpEventListener implements EventSubscriberInterface
{
    private const emailRegex = '[\w\d.!#$%&\'*+\\/=?^_`{|}~-]+@[\w\d](?:[\w\d-]{0,61}[\w\d])?(?:\.[\w\d](?:[\w\d-]{0,61}[\w\d])?)+';

    private StorageInterface $storage;
    private ?LoggerInterface $logger;
    private SmtpNegotiations $negotiations;
    private int $messageCount = 0;

    public function __construct(StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger;
        $this->negotiations = new SmtpNegotiations();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SocketAction::connect->name => 'connect',
            SocketAction::buffer->name => 'buffer',
            SocketAction::disconnect->name => 'disconnect',
        ];
    }

    public function connect(SocketEvent $event): void
    {
        if ($event->getScheme() === Scheme::SMTP) {
            $this->negotiations->add($event, new SmtpNegotiation());
            $event->setResponse(SmtpResponseFactory::ready($event->getAddressInfo()->getAddress()));
            $event->dispatch();
        }
    }

    public function buffer(SocketEvent $event): void
    {
        if ($event->getScheme() === Scheme::SMTP) {
            $this->process($event, $event->getBuffer());
        }
    }

    public function disconnect(SocketEvent $event): void
    {
        if ($event->getScheme() !== Scheme::SMTP) {
            return;
        }

        if ($this->negotiations->has($event)) {
            $this->negotiations->remove($event);
        }
    }

    private function process(SocketEvent $event, null|string|Stringable $buffer): void
    {
        if ($this->negotiations->has($event) && $this->negotiations->get($event)->hasHeaders()) {
            $this->dataStream($event, $buffer);

            return;
        }

        $messages = Helper::bufferToLines($buffer);
        foreach ($messages as $message) {
            try {
                $this->match($event, $message);
            } catch (SmtpNegotiationException $e) {
                $this->logger?->warning($e->getMessage());
                $event->setResponse(SmtpResponseFactory::badSequenceOfCommands($e));
                $event->dispatch();
                break;
            } catch (SmtpSyntaxException $e) {
                $message = 'Syntax error, command unrecognized: ' . $e->getMessage();
                $this->logger?->warning($message);
                $event->setResponse(SmtpResponseFactory::syntaxError($message));
                $event->dispatch();
                break;
            }
        }
    }

    private function match(SocketEvent $event, ?string $message): void
    {
        $hostAddress = $event->getAddressInfo()->getAddress();

        match (1) {
            preg_match('/^HELO\s+\[?([\d\w.-]+)]?$/u', $message, $matches) => $event->setResponse(SmtpResponseFactory::hello($hostAddress, $matches[1]))->dispatch(),
            preg_match('/^EHLO\s+\[?([\d\w.-]+)]?$/u', $message, $matches) => $event->setResponse(SmtpResponseFactory::eHello($hostAddress, $matches[1]))->dispatch(),
            preg_match('/^MAIL FROM:\s*<?(' . self::emailRegex . ')>?$/u', $message, $matches) => $this->mailFrom($event, $matches[1]),
            preg_match('/^RCPT TO:\s*<?(' . self::emailRegex . ')>?$/u', $message, $matches) => $this->rcptTo($event, $matches[1]),
            preg_match('/^DATA\s*$/u', $message, $matches) => $this->data($event),
            preg_match('/^RSET\s*$/u', $message, $matches) => $this->reset($event),
            preg_match('/^QUIT\s*$/u', $message, $matches) => $this->quit($event),
            preg_match('/^NOOP\s*$/u', $message, $matches) => $event->setResponse(SmtpResponseFactory::ok())->dispatch(),
            preg_match('/^HELP\s*$/ui', $message, $matches) => $event->setResponse(SmtpResponseFactory::help())->dispatch(),
            preg_match('/^(RELAY|SEND|SOML|SAML|TLS|TURN)\s*$/u', $message, $matches) => $event->setResponse(SmtpResponseFactory::notImplemented($matches[1]))->dispatch(),
            preg_match('/^\r\n$/u', $message) => $event->dispatch(),
            default => $event->setResponse(SmtpResponseFactory::syntaxError())->dispatch(),
        };
    }

    private function mailFrom(SocketEvent $event, ?string $address): void
    {
        $negotiation = $this->negotiations->get($event);
        $negotiation->mailFrom($address);
        $event->setResponse(SmtpResponseFactory::mailFrom());
        $event->dispatch();
    }

    private function rcptTo(SocketEvent $event, ?string $address): void
    {
        $this->negotiations->get($event)->rcptTo($address);
        $event->setResponse(SmtpResponseFactory::rcptTo());
        $event->dispatch();
    }

    private function data(SocketEvent $event): void
    {
        $this->negotiations->get($event)->data();
        $event->setResponse(SmtpResponse::create(SmtpResponseCode::StartMailInput, 'Send message content; end with <CRLF>.<CRLF>'));
        $event->dispatch();
    }

    private function dataStream(SocketEvent $event, null|string|Stringable $data): void
    {
        if ($data === null) {
            return;
        }
        $data = "{$data}";
        $negotiation = $this->negotiations->get($event);
        if (!$negotiation->hasHeaders()) {
            if (trim($data) === '') {
                return;
            }
            throw new SmtpSyntaxException($data);
        }

        if (preg_match('#^(\X+)\R(\.)\R$#u', $data, $matches)) {
            $negotiation->addContent($matches[1]);
            $data = $matches[2];
        } else {
            $negotiation->addContent($data);
        }

        if (trim($data) === '.') {
            $this->storage->add($negotiation->getContent());
            $event->setResponse(SmtpResponseFactory::ok('message accepted for delivery: queued as ' . ++$this->messageCount));
            $event->dispatch();
            $negotiation->reset();
        }
    }

    private function reset(SocketEvent $event): void
    {
        if ($this->negotiations->has($event)) {
            $this->negotiations->remove($event);
        }
        $event->dispatch();
        $event->setResponse(SmtpResponseFactory::ok());
    }

    private function quit(SocketEvent $event): void
    {
        if ($this->negotiations->has($event)) {
            $this->negotiations->remove($event);
        }
        $event->setResponse(SmtpResponseFactory::quit());
        $event->dispatch();
        $event->disconnect();
    }
}
