<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Parser\AbstractFieldTypeParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

/**
 * Configuration parser handling RichText field type related config.
 */
class RichText extends AbstractFieldTypeParser
{
    public const CLASSES_SA_SETTINGS_ID = 'fieldtypes.ezrichtext.classes';
    private const CLASSES_NODE_KEY = 'classes';

    public const ATTRIBUTES_SA_SETTINGS_ID = 'fieldtypes.ezrichtext.attributes';
    private const ATTRIBUTES_NODE_KEY = 'attributes';
    private const ATTRIBUTE_TYPE_NODE_KEY = 'type';
    private const ATTRIBUTE_TYPE_CHOICE = 'choice';
    private const ATTRIBUTE_TYPE_BOOLEAN = 'boolean';
    private const ATTRIBUTE_TYPE_STRING = 'string';
    private const ATTRIBUTE_TYPE_NUMBER = 'number';

    // constants common for OE custom classes and data attributes configuration
    private const ELEMENT_NODE_KEY = 'element';
    private const DEFAULT_VALUE_NODE_KEY = 'default_value';
    private const CHOICES_NODE_KEY = 'choices';
    private const REQUIRED_NODE_KEY = 'required';
    private const MULTIPLE_NODE_KEY = 'multiple';

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
        // for BC setup deprecated configuration
        $this->setupDeprecatedConfiguration($nodeBuilder);

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

        $this->buildOnlineEditorConfiguration($nodeBuilder);
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
            if (isset($scopeSettings['fieldtypes']['ezrichtext']['output_custom_tags'])) {
                $scopeSettings['fieldtypes.ezrichtext.output_custom_xsl'] = $scopeSettings['fieldtypes']['ezrichtext']['output_custom_tags'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['output_custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['edit_custom_tags'])) {
                $scopeSettings['fieldtypes.ezrichtext.edit_custom_xsl'] = $scopeSettings['fieldtypes']['ezrichtext']['edit_custom_tags'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['edit_custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['input_custom_tags'])) {
                $scopeSettings['fieldtypes.ezrichtext.input_custom_xsl'] = $scopeSettings['fieldtypes']['ezrichtext']['input_custom_tags'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['input_custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['custom_tags'])) {
                $scopeSettings['fieldtypes.ezrichtext.custom_tags'] = $scopeSettings['fieldtypes']['ezrichtext']['custom_tags'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['custom_styles'])) {
                $scopeSettings['fieldtypes.ezrichtext.custom_styles'] = $scopeSettings['fieldtypes']['ezrichtext']['custom_styles'];
                unset($scopeSettings['fieldtypes']['ezrichtext']['custom_styles']);
            }

            if (isset($scopeSettings['fieldtypes']['ezrichtext']['tags'])) {
                foreach ($scopeSettings['fieldtypes']['ezrichtext']['tags'] as $name => $tagSettings) {
                    $contextualizer->setContextualParameter(
                        "fieldtypes.ezrichtext.tags.{$name}",
                        $currentScope,
                        $scopeSettings['fieldtypes']['ezrichtext']['tags'][$name]
                    );
                }
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

            $onlineEditorSettingsMap = [
                self::CLASSES_NODE_KEY => self::CLASSES_SA_SETTINGS_ID,
                self::ATTRIBUTES_NODE_KEY => self::ATTRIBUTES_SA_SETTINGS_ID,
            ];
            foreach ($onlineEditorSettingsMap as $key => $settingsId) {
                if (isset($scopeSettings['fieldtypes']['ezrichtext'][$key])) {
                    $scopeSettings[$settingsId] = $scopeSettings['fieldtypes']['ezrichtext'][$key];
                    unset($scopeSettings['fieldtypes']['ezrichtext'][$key]);
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
        $contextualizer->mapConfigArray(self::CLASSES_SA_SETTINGS_ID, $config);
        // merge attributes of the same element from different scopes
        $contextualizer->mapConfigArray(
            self::ATTRIBUTES_SA_SETTINGS_ID,
            $config,
            ContextualizerInterface::MERGE_FROM_SECOND_LEVEL
        );
    }

    /**
     * Add BC setup for deprecated configuration.
     *
     * Note: kept in separate method for readability.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder
     */
    private function setupDeprecatedConfiguration(NodeBuilder $nodeBuilder)
    {
        $nodeBuilder
            ->arrayNode('output_custom_tags')
                ->setDeprecated('DEPRECATED. Configure custom tags using custom_tags node')
                ->info('Custom XSL stylesheets to use for RichText transformation to HTML5. Useful for "custom tags".')
                ->example(
                    [
                        'path' => '%kernel.root_dir%/../src/Acme/TestBundle/Resources/myTag.xsl',
                        'priority' => 10,
                    ]
                )
                ->prototype('array')
                    ->children()
                        ->scalarNode('path')
                            ->info('Path of the XSL stylesheet to load.')
                            ->isRequired()
                        ->end()
                        ->integerNode('priority')
                            ->info('Priority in the loading order. A high value will have higher precedence in overriding XSL templates.')
                            ->defaultValue(0)
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('edit_custom_tags')
                ->setDeprecated('DEPRECATED. Configure custom tags using custom_tags node')
                ->info('Custom XSL stylesheets to use for RichText transformation to HTML5. Useful for "custom tags".')
                ->example(
                    [
                        'path' => '%kernel.root_dir%/../src/Acme/TestBundle/Resources/myTag.xsl',
                        'priority' => 10,
                    ]
                )
                ->prototype('array')
                    ->children()
                        ->scalarNode('path')
                            ->info('Path of the XSL stylesheet to load.')
                            ->isRequired()
                        ->end()
                        ->integerNode('priority')
                            ->info('Priority in the loading order. A high value will have higher precedence in overriding XSL templates.')
                            ->defaultValue(0)
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('input_custom_tags')
                ->setDeprecated('DEPRECATED. Configure custom tags using custom_tags node')
                ->info('Custom XSL stylesheets to use for RichText transformation to HTML5. Useful for "custom tags".')
                ->example(
                    [
                        'path' => '%kernel.root_dir%/../src/Acme/TestBundle/Resources/myTag.xsl',
                        'priority' => 10,
                    ]
                )
                ->prototype('array')
                    ->children()
                        ->scalarNode('path')
                            ->info('Path of the XSL stylesheet to load.')
                            ->isRequired()
                        ->end()
                        ->integerNode('priority')
                            ->info('Priority in the loading order. A high value will have higher precedence in overriding XSL templates.')
                            ->defaultValue(0)
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('tags')
                ->setDeprecated('DEPRECATED. Configure custom tags using custom_tags node')
                ->info('RichText template tags configuration.')
                ->useAttributeAsKey('key')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->info(
                        "Name of RichText template tag.\n" .
                        "'default' and 'default_inline' tag names are reserved for fallback."
                    )
                    ->example('math_equation')
                    ->children()
                        ->append(
                            $this->getTemplateNodeDefinition(
                                'Template used for rendering RichText template tag.',
                                'MyBundle:FieldType/RichText/tag:math_equation.html.twig'
                            )
                        )
                        ->variableNode('config')
                            ->info('Tag configuration, arbitrary configuration is allowed here.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Build configuration nodes strictly related to Online Editor.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder
     */
    private function buildOnlineEditorConfiguration(NodeBuilder $nodeBuilder): void
    {
        $invalidChoiceCallback = function (array $v) {
            $message = sprintf(
                'Default value must be one of the possible choices: %s, but "%s" given',
                implode(', ', $v[self::CHOICES_NODE_KEY]),
                $v[self::DEFAULT_VALUE_NODE_KEY]
            );

            throw new InvalidArgumentException($message, 1);
        };

        $nodeBuilder
            ->arrayNode(self::CLASSES_NODE_KEY)
                ->useAttributeAsKey(self::ELEMENT_NODE_KEY)
                ->arrayPrototype()
                    ->validate()
                        ->ifTrue(function (array $v) {
                            return !empty($v[self::DEFAULT_VALUE_NODE_KEY])
                                && !in_array($v[self::DEFAULT_VALUE_NODE_KEY], $v[self::CHOICES_NODE_KEY]);
                        })
                        ->then($invalidChoiceCallback)
                    ->end()
                    ->children()
                        ->arrayNode(self::CHOICES_NODE_KEY)
                            ->scalarPrototype()->end()
                            ->isRequired()
                        ->end()
                        ->booleanNode(self::REQUIRED_NODE_KEY)
                            ->defaultFalse()
                        ->end()
                        ->scalarNode(self::DEFAULT_VALUE_NODE_KEY)
                        ->end()
                        ->booleanNode(self::MULTIPLE_NODE_KEY)
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode(self::ATTRIBUTES_NODE_KEY)
                ->useAttributeAsKey(self::ELEMENT_NODE_KEY)
                ->arrayPrototype()
                    // allow dashes in data attribute name
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->validate()
                            ->always($this->getAttributesValidatorCallback($invalidChoiceCallback))
                        ->end()
                        ->children()
                            ->enumNode(self::ATTRIBUTE_TYPE_NODE_KEY)
                                ->isRequired()
                                ->values(
                                    [
                                        self::ATTRIBUTE_TYPE_CHOICE,
                                        self::ATTRIBUTE_TYPE_BOOLEAN,
                                        self::ATTRIBUTE_TYPE_STRING,
                                        self::ATTRIBUTE_TYPE_NUMBER,
                                    ]
                                )
                            ->end()
                            ->arrayNode(self::CHOICES_NODE_KEY)
                                ->validate()
                                    ->ifEmpty()->thenUnset()
                                ->end()
                                ->scalarPrototype()
                                ->end()
                            ->end()
                            ->booleanNode(self::MULTIPLE_NODE_KEY)->defaultFalse()->end()
                            ->booleanNode(self::REQUIRED_NODE_KEY)->defaultFalse()->end()
                            ->scalarNode(self::DEFAULT_VALUE_NODE_KEY)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Return validation callback which will validate custom data attributes semantic config.
     *
     * The validation validates the following rules:
     * - if a custom data attribute is not of `choice` type, it must not define `choices` list,
     * - a `default_value` of custom data attribute must be the one from `choices` list,
     * - a custom data attribute of `boolean` type must not define `required` setting.
     *
     * @param callable $invalidChoiceCallback
     *
     * @return callable
     */
    private function getAttributesValidatorCallback(callable $invalidChoiceCallback): callable
    {
        return function (array $v) use ($invalidChoiceCallback) {
            if ($v[self::ATTRIBUTE_TYPE_NODE_KEY] === self::ATTRIBUTE_TYPE_CHOICE
                && !empty($v[self::DEFAULT_VALUE_NODE_KEY])
                && !in_array($v[self::DEFAULT_VALUE_NODE_KEY], $v[self::CHOICES_NODE_KEY])
            ) {
                $invalidChoiceCallback($v);
            } elseif ($v[self::ATTRIBUTE_TYPE_NODE_KEY] === self::ATTRIBUTE_TYPE_BOOLEAN && $v[self::REQUIRED_NODE_KEY]) {
                throw new InvalidArgumentException(
                    sprintf('Boolean type does not support "%s" setting', self::REQUIRED_NODE_KEY)
                );
            } elseif ($v[self::ATTRIBUTE_TYPE_NODE_KEY] !== self::ATTRIBUTE_TYPE_CHOICE && !empty($v[self::CHOICES_NODE_KEY])) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s type does not support "%s" setting',
                        ucfirst($v[self::ATTRIBUTE_TYPE_NODE_KEY]),
                        self::CHOICES_NODE_KEY
                    )
                );
            }

            // at this point, for non-choice types, unset choice type-related settings
            if ($v[self::ATTRIBUTE_TYPE_NODE_KEY] !== self::ATTRIBUTE_TYPE_CHOICE) {
                unset($v[self::CHOICES_NODE_KEY], $v[self::MULTIPLE_NODE_KEY]);
            }

            return $v;
        };
    }
}
