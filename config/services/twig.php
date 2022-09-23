<?php

declare(strict_types=1);

use Camelot\SmtpDevServer\Twig\TwigExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services->set('twig', Environment::class)
        ->args([
            service('twig.loader'),
            [
                'debug' => true,
                'charset' => 'UTF-8',
                'strict_variables' => true,
                'autoescape' => 'html',
                'cache' => false,
                'auto_reload' => null,
                'optimizations' => -1,
            ],
        ])
        ->call('addExtension', [service(TwigExtension::class)])
        ->tag('container.preload', ['class' => FilesystemCache::class])
        ->alias(Environment::class, 'twig')
    ;

    $services->set('twig.loader.native_filesystem', FilesystemLoader::class)
        ->args([['templates'], param('kernel.project_dir')])
        ->tag('twig.loader')
        ->alias('twig.loader', 'twig.loader.native_filesystem')
    ;
};
