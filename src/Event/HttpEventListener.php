<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Enum\HttpMethod;
use Camelot\SmtpDevServer\Enum\Scheme;
use Camelot\SmtpDevServer\Enum\SocketAction;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Helper;
use Camelot\SmtpDevServer\Request\HttpNegotiation;
use Camelot\SmtpDevServer\Request\HttpNegotiations;
use Camelot\SmtpDevServer\Request\HttpRequest;
use Camelot\SmtpDevServer\Response\HttpResponseFactory;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class HttpEventListener implements EventSubscriberInterface
{
    private HttpResponseFactory $httpResponseFactory;
    private ?LoggerInterface $logger;
    private HttpNegotiations $negotiations;

    public function __construct(HttpResponseFactory $httpResponseFactory, LoggerInterface $logger = null)
    {
        $this->httpResponseFactory = $httpResponseFactory;
        $this->logger = $logger;
        $this->negotiations = new HttpNegotiations();
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
        if ($event->getScheme() !== Scheme::HTTP) {
            return;
        }

        if ($this->negotiations->has($event)) {
            throw new ServerRuntimeException(sprintf('Socket ID %s already connected.', $event->getConnectionId()));
        }

        $this->negotiations->add($event, new HttpNegotiation());
        $event->dispatch();
    }

    public function buffer(SocketEvent $event): void
    {
        if ($event->getScheme() !== Scheme::HTTP) {
            return;
        }

        try {
            $this->process($event, $event->getBuffer());
        } catch (HttpExceptionInterface $e) {
            $this->logger?->error('Error handling HTTP request: ' . $e->getMessage());
            $event->setResponse(new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR));
            $event->disconnect();
            $event->stopPropagation();

            return;
        }
    }

    public function disconnect(SocketEvent $event): void
    {
        if ($event->getScheme() !== Scheme::HTTP) {
            return;
        }

        if ($this->negotiations->has($event)) {
            $this->negotiations->remove($event);
        }
    }

    private function process(SocketEvent $event, null|string|Stringable $buffer): void
    {
        $negotiation = $this->negotiations->get($event);

        $this->match($negotiation, $buffer);

        if ($negotiation->method()->expectsBody()) {
            if ($negotiation->headers->get('content-type') === 'application/x-www-form-urlencoded') {
                parse_str($negotiation->body(), $data);
                $negotiation->request = new InputBag($data);
            }
            $negotiation->setBodyComplete();
        }

        if ($negotiation->complete()) {
            $request = HttpRequest::createFromNegotiation($negotiation);
            $this->logger?->info("Responding to request for '{$request->getPathInfo()}'");

            $event->setResponse($this->httpResponseFactory->respond($request));
            $this->negotiations->remove($event);
            $event->dispatch();
            $event->disconnect();
        }
    }

    private function match(HttpNegotiation $negotiation, null|string|Stringable $buffer): void
    {
        foreach (Helper::bufferToLines($buffer) as $message) {
            match (true) {
                $negotiation->headersComplete() => $negotiation->appendBody($message),
                (bool) preg_match('#^(?<header>[\w\d\-_]+):\s*(?<value>.+)?#u', $message, $matches) => $this->headers($negotiation, $matches['header'], $matches['value']),
                (bool) preg_match('#^(?<method>DELETE|GET|HEAD|PATCH|POST|PUT)\s+(?<uri>.+)\s+(?<protocol>HTTP/\d\.\d)?#u', $message, $matches) => $this->path($negotiation, $matches['method'], $matches['uri'], $matches['protocol'] ?? null),
                $message === '' => $negotiation->setHeadersComplete(),
                $message === "\r\n" => $negotiation->setBodyComplete(),
                default => throw new ServerRuntimeException(sprintf('Unexpected message "%s" (%s chars). Buffer:%s%s', $message, \strlen((string) $message), PHP_EOL, $buffer)),
            };
        }
    }

    private function path(HttpNegotiation $negotiation, string $method, string $uri, ?string $protocol): void
    {
        $negotiation->setMethod(HttpMethod::from(strtoupper($method)));
        $negotiation->setPath($uri);
        $negotiation->setProtocol($protocol);
    }

    private function headers(HttpNegotiation $negotiation, string $header, ?string $value): void
    {
        if (!$negotiation->path()) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Header received before path');
        }

        if (strtolower($header) === 'cookie') {
            $parts = explode(';', $value);
            foreach ($parts as $part) {
                $bits = explode('=', trim($part));
                $negotiation->cookies->set($bits[0], $bits[1] ?? '');
            }
        } else {
            $negotiation->headers->set($header, trim($value));
        }
    }
}
