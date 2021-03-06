<?php

declare(strict_types=1);

/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Pimcore\DataObject\GridColumnConfig\Service as GridColumnService;
use Pimcore\DataObject\Import\Service as ImportService;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ImportExportLocatorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->processImport($container);
        $this->processGridColumns($container);
    }

    private function processImport(ContainerBuilder $container)
    {
        $importService = $container->getDefinition(ImportService::class);

        $this->createLocatorForTaggedServices(
            $container,
            $importService,
            'import resolver',
            'pimcore.data_object.import.resolver',
            '$resolvers'
        );

        $this->createLocatorForTaggedServices(
            $container,
            $importService,
            'import operator factory',
            'pimcore.data_object.import.operator_factory',
            '$operatorFactories'
        );

        $this->createLocatorForTaggedServices(
            $container,
            $importService,
            'import value factory',
            'pimcore.data_object.import.value_factory',
            '$valueFactories'
        );
    }

    private function processGridColumns(ContainerBuilder $container)
    {
        $gridColumnService = $container->getDefinition(GridColumnService::class);

        $this->createLocatorForTaggedServices(
            $container,
            $gridColumnService,
            'grid column operator factory',
            'pimcore.data_object.grid_column_config.operator_factory',
            '$operatorFactories'
        );

        $this->createLocatorForTaggedServices(
            $container,
            $gridColumnService,
            'grid column value factory',
            'pimcore.data_object.grid_column_config.value_factory',
            '$valueFactories'
        );
    }

    private function createLocatorForTaggedServices(
        ContainerBuilder $container,
        Definition $definition,
        string $type,
        string $tag,
        string $argument
    ) {
        $resolvers = $container->findTaggedServiceIds($tag);
        $mapping = [];

        foreach ($resolvers as $id => $tagEntries) {
            foreach ($tagEntries as $tagEntry) {
                if (!isset($tagEntry['id'])) {
                    throw new InvalidDefinitionException(sprintf(
                        'The %s "%s" does not define an ID on the "%s" tag.',
                        $type,
                        $id,
                        $tag
                    ));
                }

                $mapping[$tagEntry['id']] = new Reference($id);
            }
        }

        $serviceLocator = new Definition(ServiceLocator::class, [$mapping]);
        $serviceLocator->setPublic(false);
        $serviceLocator->addTag('container.service_locator');

        $definition->setArgument($argument, $serviceLocator);
    }
}
