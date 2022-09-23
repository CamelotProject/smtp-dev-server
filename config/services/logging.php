<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services->set(StreamHandler::class)
        ->args(['%kernel.project_dir%/var/log/app.log'])
    ;

    $services->set('monolog.logger_prototype', Logger::class)
        ->abstract()
    ;

    $services->set('logger', Logger::class)
        ->parent('monolog.logger_prototype')
        ->args(['app'])
        ->call('pushHandler', [service(StreamHandler::class)])
        ->alias(Logger::class, LoggerInterface::class)
        ->alias(Logger::class, 'logger')
        ->alias(Logger::class, 'monolog.logger')
        ->private()
    ;

    $services->set('monolog.handler.stream.smtp', StreamHandler::class)
        ->args([env('resolve:SMTP_LOG_FILE'), env('SMTP_LOG_LEVEL')])
    ;
    $services->set('monolog.handler.stream.http', StreamHandler::class)
        ->args([env('resolve:HTTP_LOG_FILE'), env('HTTP_LOG_LEVEL')])
    ;

    $services->set('monolog.logger.smtp', Logger::class)
        ->call('pushHandler', [service('monolog.handler.stream.smtp')])
        ->tag('monolog.logger', ['channel' => 'smtp'])
        ->args(['smtp'])
    ;
    $services->set('monolog.logger.http', Logger::class)
        ->call('pushHandler', [service('monolog.handler.stream.http')])
        ->tag('monolog.logger', ['channel' => 'smtp'])
        ->args(['http'])
    ;
};
