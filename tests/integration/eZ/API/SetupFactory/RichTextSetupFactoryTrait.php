<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

trait RichTextSetupFactoryTrait
{
    /**
     * Load RichText package container settings.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     *
     * @throws \Exception
     */
    protected function loadRichTextSettings(ContainerBuilder $containerBuilder)
    {
        $settingsPath = realpath(__DIR__ . '/../../../../../src/lib/eZ/settings/');
        if (false === $settingsPath) {
            throw new RuntimeException('Unable to find RichText package settings');
        }

        // load core settings
        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));
        $loader->load('fieldtypes.yaml');
        $loader->load('fieldtype_services.yaml');
        $loader->load('fieldtype_external_storages.yaml');
        $loader->load('indexable_fieldtypes.yaml');
        $loader->load('storage_engines/legacy/external_storage_gateways.yaml');
        $loader->load('storage_engines/legacy/field_value_converters.yaml');

        // load test settings
        $loader = new YamlFileLoader(
            $containerBuilder,
            new FileLocator(__DIR__ . '/../../../../lib/eZ/settings')
        );
        $loader->load('common.yaml');

        $containerBuilder->addCompilerPass(new Compiler\RichTextHtml5ConverterPass());
    }
}
