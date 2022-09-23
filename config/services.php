<?php

declare(strict_types=1);

use Camelot\SmtpDevServer\Command;
use Camelot\SmtpDevServer\Controller;
use Camelot\SmtpDevServer\Event;
use Camelot\SmtpDevServer\Mailbox;
use Camelot\SmtpDevServer\Response;
use Camelot\SmtpDevServer\Server;
use Camelot\SmtpDevServer\Storage;
use Camelot\SmtpDevServer\Twig\TwigExtension;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->bind('$projectDir', '%kernel.project_dir%')
        ->bind('$dispatcher', service(EventDispatcherInterface::class))
    ;

    $services->set(Event\SmtpEventListener::class)
        ->arg('$logger', service('monolog.logger.smtp'))
        ->tag('kernel.event_subscriber')
    ;
    $services->set(Event\HttpEventListener::class)
        ->arg('$logger', service('monolog.logger.http'))
        ->tag('kernel.event_subscriber')
    ;

    $services->set(Storage\NullStorage::class);
    $services->set(Storage\MemoryStorage::class);
    $services->set(Storage\MailboxStorage::class);
    $services->alias(Storage\StorageInterface::class, Storage\MailboxStorage::class);

    $services->set('server.smtp', Server::class)
        ->public()
    ;

    $services->set('server.http', Server::class)
        ->public()
    ;

    $services->set(Mailbox::class)
        ->arg('$spoolDir', env('resolve:SMTP_SPOOL_DIR'))
        ->public()
    ;

    $services->set(Command\SmtpServerCommand::class)
        ->arg('$server', service('server.smtp'))
        ->arg('$logger', service('monolog.logger.smtp'))
        ->arg('$signalRegistry', inline_service(SignalRegistry::class))
        ->tag('console.command')
        ->public()
    ;

    $services->set(Command\HttpServerCommand::class)
        ->arg('$server', service('server.http'))
        ->arg('$logger', service('monolog.logger.http'))
        ->arg('$signalRegistry', inline_service(SignalRegistry::class))
        ->tag('console.command')
        ->public()
    ;

    $services->set(Controller\MailboxController::class)
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->set(Controller\AssetController::class)
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->set(Controller\AttachmentController::class)
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->set(Response\HttpResponseFactory::class)
        ->arg('$logger', service('monolog.logger.http'))
    ;

    $services->set(TwigExtension::class)
        ->tag('twig.extension')
    ;
};
