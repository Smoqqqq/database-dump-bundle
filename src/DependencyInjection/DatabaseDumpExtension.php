<?php

namespace Smoq\DatabaseDumpBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class DatabaseDumpExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container)
    {
        // TODO: Implement prepend() method.
    }

    public function getAlias()
    {
        return parent::getAlias();
    }
}