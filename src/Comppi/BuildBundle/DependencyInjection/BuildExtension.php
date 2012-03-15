<?php

namespace Comppi\BuildBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BuildExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($container->getParameter('kernel.environment') == 'test') {
            $databaseRootPath = $container->getParameter('comppi.build.databaseProvider.testDatabaseRootDir');
        } else {
            if (!isset($config['database_path'])) {
                throw new \InvalidArgumentException('Please set the database_path option in the application config');
            }

            $databaseRootPath = $config['database_path'];
        }

        $container->setParameter('comppi.build.databaseProvider.databaseRootDir', $databaseRootPath);
    }

    public function getNamespace()
    {
        return 'comppi_build';
    }
}
