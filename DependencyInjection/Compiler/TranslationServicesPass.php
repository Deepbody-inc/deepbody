<?php

declare(strict_types=1);

/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Pimcore\Translation\ExportDataExtractorService\ExportDataExtractorServiceInterface;
use Pimcore\Translation\ImporterService\ImporterServiceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslationServicesPass implements CompilerPassInterface
{
    /**
     * Registers each service with tag pimcore.translation.data-extractor as data extractor for the translations export data extractor service.
     * Registers each service with tag pimcore.translation.importer as importer for the translations importer service.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds('pimcore.translation.data-extractor');

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['type'])) {
                    throw new \Exception('service with tag "pimcore.translation.data-extractor" but without type registered');
                }
                $definition = $container->getDefinition(ExportDataExtractorServiceInterface::class);
                $definition->addMethodCall('registerDataExtractor', [$attributes['type'], new Reference($id)]);
            }
        }

        $providers = $container->findTaggedServiceIds('pimcore.translation.importer');

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['type'])) {
                    throw new \Exception('service with tag "pimcore.translation.data-extractor" but without type registered');
                }
                $definition = $container->getDefinition(ImporterServiceInterface::class);
                $definition->addMethodCall('registerImporter', [$attributes['type'], new Reference($id)]);
            }
        }
    }
}
