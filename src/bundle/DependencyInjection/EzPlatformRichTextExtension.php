<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection;

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

    /**
     * @deprecated aliasing Kernel RichText classes with the ones from this bundle will be dropped
     * in the next major version
     */
    const KERNEL_CLASSMAP = [
        \eZ\Publish\Core\FieldType\RichText\Value::class => \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value::class,
        \eZ\Publish\Core\FieldType\RichText\Type::class => \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Type::class,
        \eZ\Publish\Core\FieldType\RichText\SearchField::class => \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\SearchField::class,
        \eZ\Publish\Core\FieldType\RichText\Validator::class => \EzSystems\EzPlatformRichText\eZ\RichText\Validator\Validator::class,
        \eZ\Publish\Core\FieldType\RichText\Converter::class => \EzSystems\EzPlatformRichText\eZ\RichText\Converter::class,
        \eZ\Publish\Core\FieldType\RichText\RendererInterface::class => \EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface::class,
    ];

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
        $loader->load('fieldtype_services.yml');
        $loader->load('rest.yml');
        $loader->load('templating.yml');
        $loader->load('form.yml');
        $loader->load('translation.yml');

        // load Kernel BC layer
        $loader->load('bc/aliases.yml');

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
        $coreExtensionConfigFile = realpath(__DIR__ . '/../Resources/config/prepend/ezpublish.yml');
        $container->prependExtensionConfig('ezpublish', Yaml::parseFile($coreExtensionConfigFile));
        $container->addResource(new FileResource($coreExtensionConfigFile));
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
