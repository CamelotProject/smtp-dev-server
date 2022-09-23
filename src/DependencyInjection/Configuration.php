<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('smtp_dev_server');

        $root = $treeBuilder->getRootNode()->children();
        $root
            ->arrayNode('smtp')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('spool_dir')
                        ->defaultValue('%kernel.project_dir%/var/spool')
                    ->end()
                    ->scalarNode('log_file')
                        ->defaultValue('%kernel.project_dir%/var/log/smtp.log')
                    ->end()
                ->end()
            ->end()
        ;

        $root
            ->arrayNode('http')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('log_file')
                        ->defaultValue('%kernel.project_dir%/var/log/http.log')
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
