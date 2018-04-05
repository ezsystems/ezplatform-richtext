<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API;

use eZ\Publish\API\Repository\Tests\SetupFactory\Legacy as CoreLegacySetupFactory;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Used to setup the infrastructure for Repository Public API integration tests,
 * based on Repository with Legacy Storage Engine implementation.
 */
class LegacySetupFactory extends CoreLegacySetupFactory
{
    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        $settingsPath = __DIR__ . '/../../../../src/lib/eZ/settings/';

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));
        $loader->load('fieldtypes.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('fieldtype_external_storages.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('storage_engines/legacy/external_storage_gateways.yml');
        $loader->load('storage_engines/legacy/field_value_converters.yml');

        $containerBuilder->addCompilerPass(new Compiler\KernelRichTextPass());
        $containerBuilder->addCompilerPass(new Compiler\RichTextHtml5ConverterPass());
    }
}
