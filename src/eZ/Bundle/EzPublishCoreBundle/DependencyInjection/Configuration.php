<?php

/**
 * File containing the Configuration class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishCoreBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ParserInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Suggestion\Collector\SuggestionCollectorInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration extends SiteAccessConfiguration
{
    const CUSTOM_TAG_ATTRIBUTE_TYPES = ['number', 'string', 'boolean', 'choice'];

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ezplatform_richtext');

        $this->addRichTextSection($rootNode);

        // @todo injection
        // Delegate SiteAccess config to configuration parsers
        $this->mainConfigParser->addSemanticConfig($this->generateScopeBaseNode($rootNode));

        return $treeBuilder;
    }

    /**
     * Define global Semantic Configuration for RichText.
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode
     */
    private function addRichTextSection(ArrayNodeDefinition $rootNode)
    {
        $this->addCustomTagsSection(
            $rootNode->children()->arrayNode('ezrichtext')->children()
        )->end()->end()->end();
    }

    /**
     * Define RichText Custom Tags Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezpublish:
     *     ezrichtext:
     *         custom_tags:
     * </code>
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $ezRichTextNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function addCustomTagsSection(NodeBuilder $ezRichTextNode)
    {
        return $ezRichTextNode
                ->arrayNode('custom_tags')
                // workaround: take into account Custom Tag names when merging configs
                ->useAttributeAsKey('tag')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('template')
                            ->isRequired()
                        ->end()
                        ->scalarNode('icon')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('attributes')
                            ->useAttributeAsKey('attribute')
                            ->isRequired()
                            ->arrayPrototype()
                                ->beforeNormalization()
                                    ->always(
                                        function ($v) {
                                            // Workaround: set empty value to be able to unset it later on (see validation for "choices")
                                            if (!isset($v['choices'])) {
                                                $v['choices'] = [];
                                            }

                                            return $v;
                                        }
                                    )
                                ->end()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) {
                                            return $v['type'] === 'choice' && !empty($v['required']) && empty($v['choices']);
                                        }
                                    )
                                    ->thenInvalid('List of choices for required choice type attribute has to be non-empty')
                                ->end()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) {
                                            return !empty($v['choices']) && $v['type'] !== 'choice';
                                        }
                                    )
                                    ->thenInvalid('List of choices is supported by choices type only.')
                                ->end()
                                ->children()
                                    ->enumNode('type')
                                        ->isRequired()
                                        ->values(static::CUSTOM_TAG_ATTRIBUTE_TYPES)
                                    ->end()
                                    ->booleanNode('required')
                                        ->defaultFalse()
                                    ->end()
                                    ->scalarNode('default_value')
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('choices')
                                        ->scalarPrototype()->end()
                                        ->performNoDeepMerging()
                                        ->validate()
                                            ->ifEmpty()->thenUnset()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
