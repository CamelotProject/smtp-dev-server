<?php

declare(strict_types=1);

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\DependencyInjection\RoutingResolverPass;

return function (bool $debug = true): SmtpDevCachedContainer {
    if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
        $projectDir = dirname(__DIR__);
        require_once $projectDir . '/vendor/autoload.php';
    } elseif (is_file(dirname(__DIR__, 3) . '/autoload.php')) {
        $projectDir = dirname(__DIR__, 3);
        require_once $projectDir . '/autoload.php';
    } else {
        throw new LogicException('Composer autoload is missing. Try running "composer install".');
    }

    $file = "{$projectDir}/var/cache/SmtpDevCachedContainer.php";
    $containerConfigCache = new ConfigCache($file, $debug);

    if (!$containerConfigCache->isFresh()) {
        $containerBuilder = new ContainerBuilder();
        $locator = new FileLocator(Path::join($projectDir, 'config'));

        $loader = new DependencyInjection\Loader\PhpFileLoader($containerBuilder, $locator);
        $loader->load('services/services.php');
        $loader->load('services/routing.php');

        $containerBuilder->addCompilerPass(new RoutingResolverPass());

        $containerBuilder->compile();

        $dumper = new PhpDumper($containerBuilder);
        $containerConfigCache->write($dumper->dump(['class' => 'SmtpDevCachedContainer']), $containerBuilder->getResources());
    }

    require_once $file;

    return new SmtpDevCachedContainer();
};
