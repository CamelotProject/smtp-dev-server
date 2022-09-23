<?php

declare(strict_types=1);

use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCacheFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()
        ->autoconfigure()
        ->autowire()
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

    $services->set('config.resource.self_checking_resource_checker', SelfCheckingResourceChecker::class)
        ->tag('config_cache.resource_checker', ['priority' => -990])
    ;
};
