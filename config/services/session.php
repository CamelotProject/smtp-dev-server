<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\StrictSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorageFactory;
use Symfony\Component\HttpKernel\EventListener\SessionListener;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service_locator;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set('session.storage.options', []);
    $parameters->set('session.metadata.storage_key', '_smtp_dev_meta');
    $parameters->set('session.metadata.update_threshold', 0);
    $parameters->set('session.save_path', '%kernel.project_dir%/var/session');

    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services
        ->set('session.factory', SessionFactory::class)
        ->args([
            service('request_stack'),
            service('session.storage.factory'),
            [service('session_listener'), 'onSessionUsage'],
        ])
    ;

    $services->set('session.storage.factory', NativeSessionStorageFactory::class)
        ->args([
            param('session.storage.options'),
            service('session.handler'),
            inline_service(MetadataBag::class)
                ->args([
                    param('session.metadata.storage_key'),
                    param('session.metadata.update_threshold'),
                ]),
            false,
        ])
    ;

    $services->set('session.handler', StrictSessionHandler::class)
        ->args([
            inline_service(NativeFileSessionHandler::class)
                ->args([param('session.save_path')]),
        ])
    ;

    $services->set('session_listener', SessionListener::class)
        ->args([
            service_locator([
                'session_factory' => service('session.factory'),
                'logger' => service('logger'),
            ]),
            param('kernel.debug'),
            param('session.storage.options'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('kernel.reset', ['method' => 'reset'])
        ->alias(SessionListener::class, 'session_listener')
    ;

    $services->get('event_dispatcher')
        ->call('addSubscriber', [service(SessionListener::class)])
    ;
};
