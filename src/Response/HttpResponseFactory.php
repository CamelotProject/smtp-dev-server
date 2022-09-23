<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Response;

use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Request\HttpRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use Twig\Error\Error as TwigError;

final class HttpResponseFactory
{
    private ControllerResolverInterface $controllerResolver;
    private ArgumentResolverInterface $argumentResolver;
    private EventDispatcherInterface $dispatcher;
    private ?LoggerInterface $logger;
    private KernelInterface $kernel;

    public function __construct(ControllerResolverInterface $controllerResolver, ArgumentResolverInterface $argumentResolver, EventDispatcherInterface $dispatcher, LoggerInterface $logger = null)
    {
        $this->controllerResolver = $controllerResolver;
        $this->argumentResolver = $argumentResolver;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;

        $this->kernel = new class('dev', true) extends Kernel {
            public function registerBundles(): iterable
            {
                return [];
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
            }
        };
    }

    public function respond(HttpRequest $request): Response
    {
        $path = $request->getPathInfo();
        $this->logger?->debug("Responding to request for '{$path}'");

        $requestEvent = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        try {
            $this->dispatcher->dispatch($requestEvent, KernelEvents::REQUEST);
        } catch (HttpExceptionInterface $e) {
            return $this->httpException($e);
        } catch (Throwable $e) {
            return $this->httpException(new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage(), $e));
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        return $this->controller($request);
    }

    private function controller(Request $request): Response
    {
        return LazyHttpResponse::create(function (Response $response) use ($request): void {
            try {
                [$controller, $args] = $this->route($request);
                $result = $controller(...$args);

                if ($result instanceof Response) {
                    $response->headers->replace($result->headers->all());
                    $response->setStatusCode($result->getStatusCode());
                    $response->setContent($result->getContent() ?: null);
                }
            } catch (TwigError $e) {
                $m = sprintf('%s%sin %s line %s', $e->getRawMessage(), PHP_EOL, $e->getSourceContext()->getName(), $e->getTemplateLine());
                throw new ServerRuntimeException($m, $e->getCode(), $e);
            } finally {
                $responseEvent = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
                $this->dispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);
            }
        });
    }

    private function route(Request $request): array
    {
        $controller = $this->controllerResolver->getController($request);
        $args = $this->argumentResolver->getArguments($request, $controller);

        return [$controller, $args];
    }

    private function httpException(?HttpExceptionInterface $e = null): Response
    {
        $response = new Response();
        $response->setStatusCode($e->getStatusCode());
        if ($e) {
            $response->setContent($e->getMessage() . "\r\n");
        }

        return $response;
    }
}
