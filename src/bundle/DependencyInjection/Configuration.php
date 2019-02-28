<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

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
        $rootNode = $treeBuilder->root('ezrichtext');

        $sections = $rootNode->children();
        $this
            ->addCustomTagsSection($sections);
        $this
            ->addCustomStylesSection($sections)
            ->end();

        return $treeBuilder;
    }

    /**
     * Define RichText Custom Tags Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezrichtext:
     *     custom_tags:
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
                        ->scalarNode('is_inline')
                            ->defaultFalse()
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

    /**
     * Define RichText Custom Styles Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezpublish:
     *     ezrichtext:
     *         custom_styles:
     * </code>
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $ezRichTextNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function addCustomStylesSection(NodeBuilder $ezRichTextNode)
    {
        return $ezRichTextNode
                ->arrayNode('custom_styles')
                // workaround: take into account Custom Styles names when merging configs
                    ->useAttributeAsKey('style')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('template')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('inline')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ;
    }
}
