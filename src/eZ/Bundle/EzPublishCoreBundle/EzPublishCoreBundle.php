<?php

/**
 * File containing the EzPublishCoreBundle class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishCoreBundle;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\AsseticPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\BinaryContentDownloadPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ComplexSettingsPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ConfigResolverParameterPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\FieldTypeParameterProviderRegistryPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\FragmentPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ImaginePass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\QueryTypePass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\RegisterSearchEngineIndexerPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\RegisterSearchEnginePass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\RegisterStorageEnginePass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ChainRoutingPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ChainConfigResolverPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\LocalePass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ContentViewPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\LocationViewPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\BlockViewPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\RouterPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\SecurityPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\SignalSlotPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\TranslationCollectorPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ViewProvidersPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\RichTextHtml5ConverterPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\StorageConnectionPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ComplexSettings\ComplexSettingParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\DynamicSettingParser;
use eZ\Publish\Core\Base\Container\Compiler\FieldTypeCollectionPass;
use eZ\Publish\Core\Base\Container\Compiler\FieldTypeNameableCollectionPass;
use eZ\Publish\Core\Base\Container\Compiler\RegisterLimitationTypePass;
use eZ\Publish\Core\Base\Container\Compiler\Storage\ExternalStorageRegistryPass;
use eZ\Publish\Core\Base\Container\Compiler\Storage\Legacy\FieldValueConverterRegistryPass;
use eZ\Publish\Core\Base\Container\Compiler\Storage\Legacy\RoleLimitationConverterPass;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Parser as ConfigParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Security\HttpBasicFactory;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\URLHandlerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EzPublishCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RichTextHtml5ConverterPass());
    }

    public function getContainerExtension()
    {
        if (!isset($this->extension)) {
            $this->extension = new EzPublishCoreExtension(
                array(
                    new ConfigParser\FieldType\RichText(),
                )
            );
        }

        return $this->extension;
    }
}