<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class SmtpDevServerExtension extends Extension implements CompilerPassInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function process(ContainerBuilder $container): void
    {
        $dispatcher = $container->getDefinition('event_dispatcher');
        foreach ($container->findTaggedServiceIds('kernel.event_subscriber') as $id => $_) {
            $listener = $container->getDefinition($id);
            $dispatcher->addMethodCall('addSubscriber', [$listener]);
        }
    }
}
