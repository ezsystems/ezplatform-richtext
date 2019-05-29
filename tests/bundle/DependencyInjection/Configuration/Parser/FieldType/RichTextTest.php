<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension;
use eZ\Bundle\EzPublishCoreBundle\Tests\DependencyInjection\Configuration\Parser\AbstractParserTestCase;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType\RichText as RichTextConfigParser;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\EzPlatformRichTextExtension;
use EzSystems\EzPlatformRichTextBundle\EzPlatformRichTextBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;

class RichTextTest extends AbstractParserTestCase
{
    /**
     * Multidimensional array of configuration of multiple extensions ([extension => config]).
     *
     * @var array
     */
    private $extensionsConfig;

    /**
     * Get test configuration for multiple extensions.
     *
     * @return array
     */
    private function getExtensionsConfig(): array
    {
        if (null === $this->extensionsConfig) {
            foreach (['ezrichtext', 'ezpublish'] as $extensionName) {
                $this->extensionsConfig[$extensionName] = Yaml::parseFile(
                    __DIR__ . "/../../../Fixtures/{$extensionName}.yml"
                );
            }
        }

        return $this->extensionsConfig;
    }

    /**
     * Load Configuration for multiple defined extensions.
     *
     * @param array $configurationValues
     *
     * @throws \Exception
     */
    protected function load(array $configurationValues = [])
    {
        $bundle = new EzPlatformRichTextBundle();
        $bundle->build($this->container);

        // mock list of available bundles
        $this->setParameter(
            'kernel.bundles',
            ['EzPublishCoreBundle' => null, 'EzPlatformRichTextBundle' => null]
        );

        $configs = array_merge_recursive($this->getMinimalConfiguration(), $configurationValues);

        foreach ($this->container->getExtensions() as $extension) {
            if ($extension instanceof PrependExtensionInterface) {
                $extension->prepend($this->container);
            }

            $extensionAlias = $extension->getAlias();
            // when loading extension, pass only relevant configuration
            $extensionConfig = isset($configs[$extensionAlias]) ? $configs[$extensionAlias] : [];

            $extension->load([$extensionConfig], $this->container);
        }

        $this->configResolver = $this->container->get('ezpublish.config.resolver.core');
    }

    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions()
    {
        return [
            new EzPublishCoreExtension([new RichTextConfigParser()]),
            new EzPlatformRichTextExtension(),
        ];
    }

    protected function getMinimalConfiguration()
    {
        return $this->getExtensionsConfig();
    }

    public function testDefaultContentSettings()
    {
        $this->load();

        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.tags.default',
            [
                'template' => 'EzPlatformRichTextBundle:RichText/tag:default.html.twig',
            ],
            'ezdemo_site'
        );
        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.output_custom_xsl',
            [
                0 => [
                    'path' => '%kernel.root_dir%/../vendor/ezsystems/ezplatform-richtext/src/lib/eZ/RichText/Resources/stylesheets/docbook/xhtml5/output/core.xsl',
                    'priority' => 0,
                ],
            ],
            'ezdemo_site'
        );
    }

    /**
     * Test Rich Text Custom Tags invalid settings, like enabling undefined Custom Tag.
     */
    public function testRichTextCustomTagsInvalidSettings()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown RichText Custom Tag \'foo\'');

        $this->load(
            [
                'ezpublish' => [
                    'system' => [
                        'ezdemo_site' => [
                            'fieldtypes' => [
                                'ezrichtext' => [
                                    'custom_tags' => ['foo'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.custom_tags',
            ['foo'],
            'ezdemo_site'
        );
    }

    /**
     * Test expected semantic config validation for online editor settings.
     *
     * @dataProvider getOnlineEditorInvalidSettings
     *
     * @param array $config
     * @param string $expectedExceptionMessage
     *
     * @throws \Exception
     */
    public function testOnlineEditorInvalidSettingsThrowException(
        array $config,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->load(
            [
                'ezpublish' => [
                    'system' => [
                        'ezdemo_site' => [
                            'fieldtypes' => [
                                'ezrichtext' => $config,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Data provider for testOnlineEditorInvalidSettings.
     *
     * @see testOnlineEditorInvalidSettingsThrowException
     *
     * @return array
     */
    public function getOnlineEditorInvalidSettings(): array
    {
        return [
            [
                [
                    'classes' => [
                        'paragraph' => [
                            'choices' => ['class1', 'class2'],
                            'default_value' => 'class3',
                        ],
                    ],
                ],
                'Default value must be one of the possible choices',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'select-single-attr' => [
                                'type' => 'choice',
                                'choices' => ['class1', 'class2'],
                                'default_value' => 'class3',
                            ],
                        ],
                    ],
                ],
                'Default value must be one of the possible choices',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'boolean-attr' => [
                                'type' => 'boolean',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'Boolean type does not support "required" setting',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'boolean-attr' => [
                                'type' => 'number',
                                'choices' => ['foo'],
                            ],
                        ],
                    ],
                ],
                'Number type does not support "choices" setting',
            ],
        ];
    }

    /**
     * @dataProvider richTextSettingsProvider
     *
     * @param array $config
     * @param array $expected
     *
     * @throws \Exception
     */
    public function testRichTextSettings(array $config, array $expected)
    {
        $this->load(
            [
                'ezpublish' => [
                    'system' => [
                        'ezdemo_site' => $config,
                    ],
                    // @todo remove once ezpublish extension can detect ezrichtext extension
                    'ezrichtext' => $this->getExtensionsConfig()['ezrichtext'],
                ],
            ]
        );

        foreach ($expected as $key => $val) {
            $this->assertConfigResolverParameterValue($key, $val, 'ezdemo_site');
        }
    }

    public function richTextSettingsProvider()
    {
        return [
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'output_custom_tags' => [
                                ['path' => '/foo/bar.xsl', 'priority' => 123],
                                ['path' => '/foo/custom.xsl', 'priority' => -10],
                                ['path' => '/another/custom.xsl', 'priority' => 27],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.output_custom_xsl' => [
                        // Default settings will be added
                        ['path' => '%kernel.root_dir%/../vendor/ezsystems/ezplatform-richtext/src/lib/eZ/RichText/Resources/stylesheets/docbook/xhtml5/output/core.xsl', 'priority' => 0],
                        ['path' => '/foo/bar.xsl', 'priority' => 123],
                        ['path' => '/foo/custom.xsl', 'priority' => -10],
                        ['path' => '/another/custom.xsl', 'priority' => 27],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'edit_custom_tags' => [
                                ['path' => '/foo/bar.xsl', 'priority' => 123],
                                ['path' => '/foo/custom.xsl', 'priority' => -10],
                                ['path' => '/another/custom.xsl', 'priority' => 27],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.edit_custom_xsl' => [
                        // Default settings will be added
                        ['path' => '%kernel.root_dir%/../vendor/ezsystems/ezplatform-richtext/src/lib/eZ/RichText/Resources/stylesheets/docbook/xhtml5/edit/core.xsl', 'priority' => 0],
                        ['path' => '/foo/bar.xsl', 'priority' => 123],
                        ['path' => '/foo/custom.xsl', 'priority' => -10],
                        ['path' => '/another/custom.xsl', 'priority' => 27],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'input_custom_tags' => [
                                ['path' => '/foo/bar.xsl', 'priority' => 123],
                                ['path' => '/foo/custom.xsl', 'priority' => -10],
                                ['path' => '/another/custom.xsl', 'priority' => 27],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.input_custom_xsl' => [
                        // No default settings for input
                        ['path' => '/foo/bar.xsl', 'priority' => 123],
                        ['path' => '/foo/custom.xsl', 'priority' => -10],
                        ['path' => '/another/custom.xsl', 'priority' => 27],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'tags' => [
                                'default' => [
                                    'template' => 'MyBundle:FieldType/RichText/tag:default.html.twig',
                                    'config' => [
                                        'watch' => 'out',
                                        'only' => 'first level',
                                        'can' => 'be mapped to ezxml',
                                    ],
                                ],
                                'math_equation' => [
                                    'template' => 'MyBundle:FieldType/RichText/tag:math_equation.html.twig',
                                    'config' => [
                                        'some' => 'arbitrary',
                                        'hash' => [
                                            'structure' => 12345,
                                            'works' => [
                                                'drink' => 'beer',
                                                'explode' => false,
                                            ],
                                            'does not work' => [
                                                'drink' => 'whiskey',
                                                'deeble' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.tags.default' => [
                        'template' => 'MyBundle:FieldType/RichText/tag:default.html.twig',
                        'config' => [
                            'watch' => 'out',
                            'only' => 'first level',
                            'can' => 'be mapped to ezxml',
                        ],
                    ],
                    'fieldtypes.ezrichtext.tags.math_equation' => [
                        'template' => 'MyBundle:FieldType/RichText/tag:math_equation.html.twig',
                        'config' => [
                            'some' => 'arbitrary',
                            'hash' => [
                                'structure' => 12345,
                                'works' => [
                                    'drink' => 'beer',
                                    'explode' => false,
                                ],
                                'does not work' => [
                                    'drink' => 'whiskey',
                                    'deeble' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'custom_tags' => ['video', 'equation'],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.custom_tags' => ['video', 'equation'],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'embed' => [
                                'content' => [
                                    'template' => 'MyBundle:FieldType/RichText/embed:content.html.twig',
                                    'config' => [
                                        'have' => [
                                            'spacesuit' => [
                                                'travel' => true,
                                            ],
                                        ],
                                    ],
                                ],
                                'location_inline_denied' => [
                                    'template' => 'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig',
                                    'config' => [
                                        'have' => [
                                            'location' => [
                                                'index' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.embed.content' => [
                        'template' => 'MyBundle:FieldType/RichText/embed:content.html.twig',
                        'config' => [
                            'have' => [
                                'spacesuit' => [
                                    'travel' => true,
                                ],
                            ],
                        ],
                    ],
                    'fieldtypes.ezrichtext.embed.location_inline_denied' => [
                        'template' => 'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig',
                        'config' => [
                            'have' => [
                                'location' => [
                                    'index' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'classes' => [
                                'paragraph' => [
                                    'choices' => ['class1', 'class2'],
                                    'required' => true,
                                    'default_value' => 'class1',
                                    'multiple' => true,
                                ],
                                'headline' => [
                                    'choices' => ['class3', 'class4'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.classes' => [
                        'paragraph' => [
                            'choices' => ['class1', 'class2'],
                            'required' => true,
                            'default_value' => 'class1',
                            'multiple' => true,
                        ],
                        'headline' => [
                            'choices' => ['class3', 'class4'],
                            'required' => false,
                            'multiple' => true,
                        ],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'attributes' => [
                                'paragraph' => [
                                    'select-single-attr' => [
                                        'choices' => ['class1', 'class2'],
                                        'type' => 'choice',
                                        'required' => true,
                                        'default_value' => 'class1',
                                    ],
                                ],
                                'headline' => [
                                    'text-attr' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.attributes' => [
                        'paragraph' => [
                            'select-single-attr' => [
                                'choices' => ['class1', 'class2'],
                                'type' => 'choice',
                                'required' => true,
                                'default_value' => 'class1',
                                'multiple' => false,
                            ],
                        ],
                        'headline' => [
                            'text-attr' => [
                                'type' => 'string',
                                'required' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
