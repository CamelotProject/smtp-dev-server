<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

use Camelot\SmtpDevServer\Request\HttpRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

final class HttpEventListener implements EventSubscriberInterface
{
    private ControllerResolverInterface $controllerResolver;
    private ArgumentResolverInterface $argumentResolver;
    private UrlMatcherInterface $matcher;
    private ?LoggerInterface $logger;
    /** @var HttpRequest[] */
    private array $pending = [];

    public function __construct(ControllerResolverInterface $controllerResolver, ArgumentResolverInterface $argumentResolver, UrlMatcherInterface $matcher, LoggerInterface $logger = null)
    {
        $this->controllerResolver = $controllerResolver;
        $this->argumentResolver = $argumentResolver;
        $this->matcher = $matcher;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HttpEvent::class => 'handleRequest',
        ];
    }

    public function handleRequest(HttpEvent $event): void
    {
        $message = $event->getMessage();
        match (1) {
            preg_match('#^(GET) (.+) HTTP/\d\.\d\R$#', $message, $matches) => $this->path($event, $matches[2], $matches[1]),
            preg_match('#^\s*\R$#', $message) => $this->respond($event),
            default => $this->headers($event, $message),
        };
    }

    private function path(HttpEvent $event, string $path, string $method): void
    {
        $this->pending[$event->getClient()->id()] = Request::create($path, $method);
    }

    private function headers(HttpEvent $event, string $header): void
    {
        preg_match('#([\w\d\-_]+):\s*(.+)\R$#', $header, $matches);
        if ($matches[1] ?? false) {
            $this->pending[$event->getClient()->id()]->headers->set($matches[1], trim($matches[2]));
        }
    }

    private function respond(HttpEvent $event): void
    {
        $request = $this->pending[$event->getClient()->id()];

        $path = $request->getPathInfo();
        $this->logger?->info("Responding to request for '{$path}'");

        try {
            $match = $this->matcher->matchRequest($request);
        } catch (ResourceNotFoundException $e) {
            $this->notFound($event);

            return;
        }

        if ($match['_controller'] ?? false) {
            $request->attributes->add($match);
            $controller = $this->controllerResolver->getController($request);
            $args = $this->argumentResolver->getArguments($request, $controller);
            $event->setResponse($controller(...$args));

            return;
        }

        $this->notFound($event);
    }

    private function notFound(HttpEvent $event): void
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
        $event->setResponse($response);
    }
}
