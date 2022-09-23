<?php

declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\Generator\Dumper\CompiledUrlGeneratorDumper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();

    $parameters->set('router.resource', 'config/routes.php');
    $parameters->set('request_listener.http_port', 80);
    $parameters->set('request_listener.https_port', 443);
    $parameters->set('router.request_context.base_url', '');
    $parameters->set('router.request_context.host', 'localhost');
    $parameters->set('router.request_context.scheme', 'https');

    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->bind('$projectDir', '%kernel.project_dir%')
    ;

    $services->set('controller_resolver', ContainerControllerResolver::class)
        ->args([
            service('service_container'),
            service('logger'),
        ])
        ->tag('monolog.logger', ['channel' => 'request'])
        ->alias(ControllerResolverInterface::class, 'controller_resolver')
    ;

    $services->set('argument_metadata_factory', ArgumentMetadataFactory::class);

    $services->set('argument_resolver', ArgumentResolver::class)
        ->alias(ArgumentResolverInterface::class, 'argument_resolver')
    ;

    $services->set('request_stack', RequestStack::class)
        ->alias(RequestStack::class, 'request_stack')
        ->public()
    ;

    $services->set('routing.resolver', LoaderResolver::class);

    $services->set('file_locator', FileLocator::class)
        ->arg('$paths', '%kernel.project_dir%')
        ->alias(FileLocator::class, 'file_locator')
    ;

    $services->set('routing.loader.php', Routing\Loader\PhpFileLoader::class)
        ->args([
            service('file_locator'),
            '%kernel.environment%',
        ])
        ->tag('routing.loader')
    ;
    $services->set('routing.loader', DelegatingLoader::class)
        ->public()
        ->args([
            service('routing.resolver'),
            [], // Default options
            [], // Default requirements
        ])
    ;
    $services->set('router.default', Router::class)
        ->arg('$loader', service('routing.loader'))
        ->arg('$resource', param('router.resource'))
        ->arg('$options', [
            'cache_dir' => param('kernel.cache_dir'),
            'debug' => param('kernel.debug'),
            'generator_class' => CompiledUrlGenerator::class,
            'generator_dumper_class' => CompiledUrlGeneratorDumper::class,
            'matcher_class' => CompiledUrlMatcher::class,
            'matcher_dumper_class' => CompiledUrlMatcherDumper::class,
        ])
        ->arg('$context', service('router.request_context'))
        ->arg('$logger', service('logger'))
        ->arg('$defaultLocale', param('kernel.default_locale'))
        ->call('setConfigCacheFactory', [
            service('config_cache_factory'),
        ])
        ->tag('monolog.logger', ['channel' => 'router'])
        ->alias('router', 'router.default')->public()
        ->alias(RouterInterface::class, 'router')
        ->alias(UrlGeneratorInterface::class, 'router')
        ->alias(UrlMatcherInterface::class, 'router')
        ->alias(RequestContextAwareInterface::class, 'router')
    ;

    $services->set('router.request_context', RequestContext::class)
        ->factory([RequestContext::class, 'fromUri'])
        ->args([
            param('router.request_context.base_url'),
            param('router.request_context.host'),
            param('router.request_context.scheme'),
            param('request_listener.http_port'),
            param('request_listener.https_port'),
        ])
        ->call('setParameter', [
            '_functions',
            service('router.expression_language_provider')->ignoreOnInvalid(),
        ])
        ->alias(RequestContext::class, 'router.request_context')
    ;

    $services->set(RouterListener::class)
        ->arg('$matcher', service('router'))
    ;

    $services->get('event_dispatcher')
        ->call('addSubscriber', [service(RouterListener::class)])
    ;
};
