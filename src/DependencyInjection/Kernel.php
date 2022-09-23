<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\DependencyInjection;

use SmtpDevCachedContainer;
use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\DependencyInjection\RoutingResolverPass;

final class Kernel
{
    private string $environment;
    private bool $debug;
    private ?ContainerInterface $container = null;

    public function __construct(string $environment, bool $debug)
    {
        $this->environment = $environment;
        $this->debug = $debug;
    }

    /** Boots the current kernel. */
    public function boot(): void
    {
        $containerFile = Path::join($this->getCacheDir(), 'SmtpDevCachedContainer.php');
        $containerConfigCache = new ConfigCache($containerFile, $this->debug);

        if (!$containerConfigCache->isFresh()) {
            $containerBuilder = $this->configureContainerBuilder();
            $containerBuilder->compile();

            $dumper = new PhpDumper($containerBuilder);
            $containerConfigCache->write($dumper->dump(['class' => 'SmtpDevCachedContainer']), $containerBuilder->getResources());
        }

        require_once $containerFile;
        $container = new SmtpDevCachedContainer();

        $this->container = $container;
    }

    /** Shutdowns the kernel. */
    public function shutdown(): void
    {
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /** Gets the application root dir (path of the project's composer file). */
    public function getProjectDir(): string
    {
        if (!isset($this->projectDir)) {
            $r = new \ReflectionObject($this);

            if (!is_file($dir = $r->getFileName())) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!is_file($dir . '/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    public function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new \RuntimeException('Kernel not booted.');
        }

        return $this->container;
    }

    private function configureContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('kernel.debug', $this->debug);
        $containerBuilder->setParameter('kernel.environment', $this->environment);
        $containerBuilder->setParameter('kernel.project_dir', $this->getProjectDir());
        $containerBuilder->setParameter('kernel.cache_dir', $this->getCacheDir());
        $containerBuilder->setParameter('kernel.default_locale', 'en');

        $containerBuilder->registerExtension(new SmtpDevServerExtension());
        $containerBuilder->addCompilerPass(new RoutingResolverPass());

        $locator = new FileLocator($this->getConfigDir());
        $globFileLoader = new Loader\GlobFileLoader($containerBuilder, $locator);
        $phpFileLoader = new Loader\PhpFileLoader($containerBuilder, $locator, $this->environment, new ConfigBuilderGenerator($this->getCacheDir()));
        $resolver = new LoaderResolver([$globFileLoader, $phpFileLoader]);
        $delegatingLoader = new DelegatingLoader($resolver);

        $delegatingLoader->load('services/*.php', 'glob');
        $phpFileLoader->load('services.php', 'php');
        $phpFileLoader->load('config.php', 'php');

        return $containerBuilder;
    }

    /** Gets the path to the configuration directory. */
    private function getConfigDir(): string
    {
        return $this->getProjectDir() . '/config';
    }
}
