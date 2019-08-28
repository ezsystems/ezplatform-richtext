<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection;

use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * eZ Platform RichText Field Type Bundle extension.
 */
class EzPlatformRichTextExtension extends Extension implements PrependExtensionInterface
{
    const RICHTEXT_CUSTOM_STYLES_PARAMETER = 'ezplatform.ezrichtext.custom_styles';
    const RICHTEXT_CUSTOM_TAGS_PARAMETER = 'ezplatform.ezrichtext.custom_tags';
    const RICHTEXT_ALLOY_EDITOR_PARAMETER = 'ezplatform.ezrichtext.alloy_editor';
    public const RICHTEXT_CONFIGURATION_PROVIDER_TAG = 'ezplatform.ezrichtext.configuration.provider';

    public function getAlias()
    {
        return 'ezrichtext';
    }

    /**
     * Load eZ Platform RichText Field Type Bundle configuration.
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $ezLoader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../lib/eZ/settings')
        );
        $ezLoader->load('fieldtypes.yaml');
        $ezLoader->load('fieldtype_services.yaml');
        $ezLoader->load('fieldtype_external_storages.yaml');
        $ezLoader->load('indexable_fieldtypes.yaml');
        $ezLoader->load('storage_engines/legacy/external_storage_gateways.yaml');
        $ezLoader->load('storage_engines/legacy/field_value_converters.yaml');

        $container
            ->registerForAutoconfiguration(Provider::class)
            ->addTag(static::RICHTEXT_CONFIGURATION_PROVIDER_TAG);

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('fieldtype_services.yaml');
        $loader->load('rest.yaml');
        $loader->load('templating.yaml');
        $loader->load('form.yaml');
        $loader->load('translation.yaml');
        $loader->load('configuration.yaml');
        $loader->load('api.yaml');

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
        $customTagsConfig = $config['custom_tags'] ?? [];
        $customStylesConfig = $config['custom_styles'] ?? [];
        $alloyEditorConfig = $config['alloy_editor'] ?? [];

        $availableSiteAccesses = $container->hasParameter('ezpublish.siteaccess.list')
            ? $container->getParameter('ezpublish.siteaccess.list')
            : [];

        $this->validateCustomTemplatesConfig(
            $availableSiteAccesses,
            $customTagsConfig,
            'custom_tags',
            'Tag',
            $container
        );
        $this->validateCustomTemplatesConfig(
            $availableSiteAccesses,
            $customStylesConfig,
            'custom_styles',
            'Style',
            $container
        );

        $container->setParameter(static::RICHTEXT_CUSTOM_TAGS_PARAMETER, $customTagsConfig);
        $container->setParameter(static::RICHTEXT_CUSTOM_STYLES_PARAMETER, $customStylesConfig);
        $container->setParameter(static::RICHTEXT_ALLOY_EDITOR_PARAMETER, $alloyEditorConfig);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->prependEzPublishConfiguration($container);
        $this->prependEzRichTextConfiguration($container);
    }

    private function prependEzPublishConfiguration(ContainerBuilder $container): void
    {
        $coreExtensionConfigFile = realpath(__DIR__ . '/../Resources/config/prepend/ezpublish.yaml');
        $container->prependExtensionConfig('ezpublish', Yaml::parseFile($coreExtensionConfigFile));
        $container->addResource(new FileResource($coreExtensionConfigFile));
    }

    private function prependEzRichTextConfiguration(ContainerBuilder $container): void
    {
        $richTextExtensionConfigFile = realpath(__DIR__ . '/../Resources/config/prepend/ezrichtext.yaml');
        $container->prependExtensionConfig('ezrichtext', Yaml::parseFile($richTextExtensionConfigFile));
        $container->addResource(new FileResource($richTextExtensionConfigFile));
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * Validate Custom Templates (Tags, Styles) SiteAccess-defined configuration against a global one.
     *
     * @param array $availableSiteAccesses a list of available SiteAccesses
     * @param array $config Custom Template configuration
     * @param string $nodeName Custom Template node name
     * @param string $type Custom Template type name
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function validateCustomTemplatesConfig(
        array $availableSiteAccesses,
        array $config,
        string $nodeName,
        string $type,
        ContainerBuilder $container
    ) {
        $namespace = 'ezsettings';
        $definedCustomTemplates = array_keys($config);
        // iterate manually through available Scopes as scope context is not available
        foreach ($availableSiteAccesses as $siteAccessName) {
            $enabledTemplatesParamName = "{$namespace}.{$siteAccessName}.fieldtypes.ezrichtext.{$nodeName}";
            if (!$container->hasParameter($enabledTemplatesParamName)) {
                continue;
            }

            foreach ($container->getParameter($enabledTemplatesParamName) as $customTemplateName) {
                if (!in_array($customTemplateName, $definedCustomTemplates)) {
                    throw new InvalidConfigurationException(
                        "Unknown RichText Custom {$type} '{$customTemplateName}' (required by the '{$siteAccessName}' SiteAccess)"
                    );
                }
            }
        }
    }
}
