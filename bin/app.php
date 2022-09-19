<?php

declare(strict_types=1);

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Filesystem\Path;

return function (string $projectDir, bool $debug = true): SmtpDevCachedContainer {
    $file = "{$projectDir}/var/cache/SmtpDevCachedContainer.php";
    $containerConfigCache = new ConfigCache($file, $debug);

    if (!$containerConfigCache->isFresh()) {
        $containerBuilder = new ContainerBuilder();
        $locator = new FileLocator(Path::join($projectDir, 'config'));

        $loader = new DependencyInjection\Loader\PhpFileLoader($containerBuilder, $locator);
        $loader->load('services/services.php');

        $containerBuilder->compile();

        $dumper = new PhpDumper($containerBuilder);
        $containerConfigCache->write($dumper->dump(['class' => 'SmtpDevCachedContainer']), $containerBuilder->getResources());
    }

    require_once $file;

    return new SmtpDevCachedContainer();
};
