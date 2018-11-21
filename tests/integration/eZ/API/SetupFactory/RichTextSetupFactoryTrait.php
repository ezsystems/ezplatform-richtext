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

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));
        $loader->load('fieldtypes.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('fieldtype_external_storages.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('storage_engines/legacy/external_storage_gateways.yml');
        $loader->load('storage_engines/legacy/field_value_converters.yml');

        $containerBuilder->addCompilerPass(new Compiler\RichTextHtml5ConverterPass());
    }
}
