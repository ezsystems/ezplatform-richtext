<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler\KernelRichTextPass;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler\RichTextHtml5ConverterPass;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType\RichText;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\EzPlatformRichTextExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * eZ Platform RichText FieldType Bundle.
 */
class EzPlatformRichTextBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RichTextHtml5ConverterPass());
        $container->addCompilerPass(new KernelRichTextPass());
        $this->registerConfigParser($container);
    }

    public function registerConfigParser(ContainerBuilder $container)
    {
        $this->getCoreExtension($container)->addConfigParser(new RichText());
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension
     */
    protected function getCoreExtension(ContainerBuilder $container): EzPublishCoreExtension
    {
        return $container->getExtension('ezpublish');
    }

    public function getContainerExtension()
    {
        if (!isset($this->extension)) {
            $this->extension = new EzPlatformRichTextExtension();
        }

        return $this->extension;
    }
}
