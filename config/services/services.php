<?php

declare(strict_types=1);

use Camelot\SmtpDevServer\Command;
use Camelot\SmtpDevServer\Event;
use Camelot\SmtpDevServer\Server;
use Camelot\SmtpDevServer\Storage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\ResourceCheckerConfigCacheFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as EventDispatcherInterfaceComponentAlias;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();

    $parameters->set('kernel.debug', true);
    $parameters->set('kernel.environment', 'dev');
    $parameters->set('kernel.project_dir', dirname(__DIR__, 2));
    $parameters->set('kernel.cache_dir', '%kernel.project_dir%/var/cache');
    $parameters->set('kernel.default_locale', 'en');

    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->bind('$projectDir', '%kernel.project_dir%')
    ;

    $services->set('parameter_bag', ContainerBag::class)
        ->args([
            service('service_container'),
        ])
        ->alias(ContainerBagInterface::class, 'parameter_bag')
        ->alias(ParameterBagInterface::class, 'parameter_bag')
    ;

    $services->set('config_cache_factory', ResourceCheckerConfigCacheFactory::class)
        ->args([
            tagged_iterator('config_cache.resource_checker'),
        ])
    ;

    $services->set(StreamHandler::class)
        ->args(['%kernel.project_dir%/var/log/app.log'])
    ;
    $services->set('logger', Logger::class)
        ->args(['app'])
        ->call('pushHandler', [service(StreamHandler::class)])
        ->alias(Logger::class, LoggerInterface::class)
        ->alias(Logger::class, 'logger')
        ->alias(Logger::class, 'monolog.logger')
    ;

    $services->set('monolog.handler.stream.smtp', StreamHandler::class)
        ->args(['%kernel.project_dir%/var/log/smtp.log', 100]) // Level::Debug
    ;

    $services->set('monolog.logger.smtp', Logger::class)
        ->tag('monolog.logger', ['channel' => 'smtp'])
        ->args(['smtp'])
    ;

    $services->set('event_dispatcher', EventDispatcher::class)
        ->call('addSubscriber', [service(Event\SmtpEventListener::class)])
        ->public()
        ->tag('container.hot_path')
        ->tag('event_dispatcher.dispatcher', ['name' => 'event_dispatcher'])
        ->alias(EventDispatcherInterfaceComponentAlias::class, 'event_dispatcher')
        ->alias(EventDispatcherInterface::class, 'event_dispatcher')
    ;
    $services->set(Event\SmtpEventListener::class);

    $services->set(Storage\NullStorage::class);
    $services->set(Storage\MemoryStorage::class);
    $services->set(Storage\MailboxStorage::class);
    $services->alias(Storage\StorageInterface::class, Storage\MailboxStorage::class);

    $services->set('server.smtp', Server::class)
        ->args([service(EventDispatcherInterface::class), service('monolog.logger.smtp')])
    ;

    $services->set(Command\SmtpServerCommand::class)
        ->args([service('server.smtp'), service('monolog.logger.smtp')])
        ->tag('console.command')
        ->public()
    ;
};
