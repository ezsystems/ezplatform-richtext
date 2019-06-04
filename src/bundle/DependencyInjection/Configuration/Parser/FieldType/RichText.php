<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Parser\AbstractFieldTypeParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

/**
 * Configuration parser handling RichText field type related config.
 */
class RichText extends AbstractFieldTypeParser
{
    /**
     * Returns the fieldType identifier the config parser works for.
     * This is to create the right configuration node under system.<siteaccess_name>.fieldtypes.
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return 'ezrichtext';
    }

    /**
     * Adds semantic configuration definition.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder Node just under ezpublish.system.<siteaccess>
     */
    public function addFieldTypeSemanticConfig(NodeBuilder $nodeBuilder)
    {
        $nodeBuilder
            ->arrayNode('embed')
                ->info('RichText embed tags configuration.')
                ->children()
                    ->arrayNode('content')
                        ->info('Configuration for RichText block-level Content embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Content embed tags.',
                                    'MyBundle:FieldType/RichText/embed:content.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_denied')
                        ->info('Configuration for RichText block-level Content embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Content embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:content_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_inline')
                        ->info('Configuration for RichText inline-level Content embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Content embed tags.',
                                    'MyBundle:FieldType/RichText/embed:content_inline.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_inline_denied')
                        ->info('Configuration for RichText inline-level Content embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Content embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:content_inline_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location')
                        ->info('Configuration for RichText block-level Location embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Location embed tags.',
                                    'MyBundle:FieldType/RichText/embed:location.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_denied')
                        ->info('Configuration for RichText block-level Location embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Location embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:location_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_inline')
                        ->info('Configuration for RichText inline-level Location embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Location embed tags.',
                                    'MyBundle:FieldType/RichText/embed:location_inline.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_inline_denied')
                        ->info('Configuration for RichText inline-level Location embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Location embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // RichText Custom Tags configuration (list of Custom Tags enabled for current SiteAccess scope)
        $nodeBuilder
            ->arrayNode('custom_tags')
                ->info('List of RichText Custom Tags enabled for the current scope. The Custom Tags must be defined in ezpublish.ezrichtext.custom_tags Node.')
                ->scalarPrototype()->end()
            ->end();

        // RichText Custom Styles configuration (list of Custom Styles enabled for current SiteAccess scope)
        $nodeBuilder
            ->arrayNode('custom_styles')
                ->info('List of RichText Custom Styles enabled for the current scope. The Custom Styles must be defined in ezpublish.ezrichtext.custom_styles Node.')
                ->scalarPrototype()->end()
            ->end();
    }

    /**
     * @param string $info
     * @param string $example
     *
     * @return \Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition
     */
    protected function getTemplateNodeDefinition($info, $example)
    {
        $templateNodeDefinition = new ScalarNodeDefinition('template');
        $templateNodeDefinition
            ->info($info)
            ->example($example)
            ->isRequired()
            ->cannotBeEmpty();

        return $templateNodeDefinition;
    }

    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
    {
        if (!empty($scopeSettings['fieldtypes'])) {
            // Workaround to be able to use Contextualizer::mapConfigArray() which only supports first level entries.
            if (isset($scopeSettings['fieldtypes']['ezrichtext']['custom_tags'])) {
                $scopeSettings['fieldtypes.ezrichtext.custom_tags'] = $scopeSettings['fieldtypes']['ezrichtext']['custom_tags'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['custom_styles'])) {
                $scopeSettings['fieldtypes.ezrichtext.custom_styles'] = $scopeSettings['fieldtypes']['ezrichtext']['custom_styles'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['custom_styles']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['embed'])) {
                foreach ($scopeSettings['fieldtypes']['ezrichtext']['embed'] as $type => $embedSettings) {
                    $contextualizer->setContextualParameter(
                        "fieldtypes.ezrichtext.embed.{$type}",
                        $currentScope,
                        $scopeSettings['fieldtypes']['ezrichtext']['embed'][$type]
                    );
                }
            }
        }
    }

    public function postMap(array $config, ContextualizerInterface $contextualizer)
    {
        $contextualizer->mapConfigArray('fieldtypes.ezrichtext.custom_tags', $config);
        $contextualizer->mapConfigArray('fieldtypes.ezrichtext.custom_styles', $config);
        $contextualizer->mapConfigArray('fieldtypes.ezrichtext.output_custom_xsl', $config);
        $contextualizer->mapConfigArray('fieldtypes.ezrichtext.edit_custom_xsl', $config);
        $contextualizer->mapConfigArray('fieldtypes.ezrichtext.input_custom_xsl', $config);
    }
}
