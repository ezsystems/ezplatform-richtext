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
        ];
    }
}
