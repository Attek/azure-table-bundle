<?php declare(strict_types=1);

namespace Lsyh\TableServiceBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class TableServiceBundle extends AbstractBundle
{

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
          ->children()
          ->scalarNode('azure_url')->end()
          ->scalarNode('azure_table_name')->end()
          ->scalarNode('azure_sas_token')->end()
          ->end()
          ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('azure_table_service.azure_url', $config['azure_url']);
        $container->parameters()->set('azure_table_service.azure_sas_token', $config['azure_sas_token']);
        $container->parameters()->set('azure_table_service.azure_table_name', $config['azure_table_name']);

    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}