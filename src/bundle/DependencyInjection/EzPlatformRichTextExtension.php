<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

/**
 * eZ Platform RichText Field Type Bundle extension.
 */
class EzPlatformRichTextExtension extends Extension implements PrependExtensionInterface
{
    const RICHTEXT_CUSTOM_STYLES_PARAMETER = 'ezplatform.ezrichtext.custom_styles';
    const RICHTEXT_CUSTOM_TAGS_PARAMETER = 'ezplatform.ezrichtext.custom_tags';

    public function getAlias()
    {
        return 'ezrichtext';
    }

    /**
     * Load eZ Platform RichText Field Type Bundle configuration.
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $ezLoader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../lib/eZ/settings')
        );
        $ezLoader->load('fieldtypes.yml');
        $ezLoader->load('fieldtype_services.yml');
        $ezLoader->load('fieldtype_external_storages.yml');
        $ezLoader->load('indexable_fieldtypes.yml');
        $ezLoader->load('storage_engines/legacy/external_storage_gateways.yml');
        $ezLoader->load('storage_engines/legacy/field_value_converters.yml');

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('default_settings.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('rest.yml');
        $loader->load('templating.yml');
        $loader->load('form.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $this->registerRichTextConfiguration($config, $container);
    }

    /**
     * Register parameters of global RichText configuration.
     *
     * @param array $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function registerRichTextConfiguration(array $config, ContainerBuilder $container)
    {
        if (isset($config['custom_tags'])) {
            $container->setParameter(
                static::RICHTEXT_CUSTOM_TAGS_PARAMETER,
                $config['custom_tags']
            );
        }
        if (isset($config['custom_styles'])) {
            $container->setParameter(
                static::RICHTEXT_CUSTOM_STYLES_PARAMETER,
                $config['custom_styles']
            );
        }
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('ezpublish', [
            'system' => ['default' => [
                'field_templates' => [
                    [
                        'template' => 'EzPlatformRichTextBundle:RichText:content_fields.html.twig',
                        'priority' => 0,
                    ],
                ],
                'fielddefinition_settings_templates' => [
                    [
                        'template' => 'EzPlatformRichTextBundle:RichText:fielddefinition_settings.html.twig',
                        'priority' => 0,
                    ],
                ],
            ]],
        ]);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }
}
