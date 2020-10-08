<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\DependencyInjection;

use EzSystems\EzPlatformRichTextBundle\DependencyInjection\EzPlatformRichTextExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

class EzPlatformRichTextExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new EzPlatformRichTextExtension()];
    }

    /**
     * Test RichText Semantic Configuration.
     */
    public function testRichTextConfiguration(): void
    {
        $config = Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/ezrichtext.yaml')
        );
        $this->load($config);

        // Validate Custom Tags
        $this->assertTrue(
            $this->container->hasParameter(EzPlatformRichTextExtension::RICHTEXT_CUSTOM_TAGS_PARAMETER)
        );
        $expectedCustomTagsConfig = [
            'video' => [
                'template' => 'MyBundle:FieldType/RichText/tag:video.html.twig',
                'icon' => '/bundles/mybundle/fieldtype/richtext/video.svg#video',
                'attributes' => [
                    'title' => [
                        'type' => 'string',
                        'required' => true,
                        'default_value' => 'abc',
                    ],
                    'width' => [
                        'type' => 'number',
                        'required' => true,
                        'default_value' => 360,
                    ],
                    'autoplay' => [
                        'type' => 'boolean',
                        'required' => false,
                        'default_value' => null,
                    ],
                ],
                'is_inline' => false,
            ],
            'equation' => [
                'template' => 'MyBundle:FieldType/RichText/tag:equation.html.twig',
                'icon' => '/bundles/mybundle/fieldtype/richtext/equation.svg#equation',
                'attributes' => [
                    'name' => [
                        'type' => 'string',
                        'required' => true,
                        'default_value' => 'Equation',
                    ],
                    'processor' => [
                        'type' => 'choice',
                        'required' => true,
                        'default_value' => 'latex',
                        'choices' => ['latex', 'tex'],
                    ],
                ],
                'is_inline' => false,
            ],
        ];

        $this->assertSame(
            $expectedCustomTagsConfig,
            $this->container->getParameter(EzPlatformRichTextExtension::RICHTEXT_CUSTOM_TAGS_PARAMETER)
        );
    }

    /**
     * Test EzPlatformRichTextExtension prepends expected and needed core settings.
     *
     * @see \EzSystems\EzPlatformRichTextBundle\DependencyInjection\EzPlatformRichTextExtension::prepend
     */
    public function testPrepend(): void
    {
        $this->load([]);

        $actualPrependedConfig = $this->container->getExtensionConfig('ezpublish');
        // merge multiple configs returned
        $actualPrependedConfig = array_merge(...$actualPrependedConfig);

        $expectedPrependedConfig = [
            'field_templates' => [
                    [
                        'template' => '@EzPlatformRichText/RichText/content_fields.html.twig',
                        'priority' => 0,
                    ],
                ],
            'fielddefinition_settings_templates' => [
                [
                    'template' => '@EzPlatformRichText/RichText/fielddefinition_settings.html.twig',
                    'priority' => 0,
                ],
            ],
        ];

        self::assertSame(
            $expectedPrependedConfig,
            $actualPrependedConfig['system']['default']
        );
    }

    public function testThrowsExceptionOnInlineCustomTagsInNonTextToolbar(): void
    {
        $config = Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/ezrichtext.yaml')
        );
        $config['custom_tags']['video']['is_inline'] = true;
        $this->container->setParameter('ezpublish.siteaccess.list', ['admin_group']);
        $this->container->setParameter("ezsettings.admin_group.fieldtypes.ezrichtext.toolbars", [
            'some_toolbar' => [
                'buttons' => [
                    'video' => [
                        'priority' => 5,
                        'visible' => true,
                    ],
                ],
            ],
        ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Toolbar "some_toolbar" configured in "ezsettings.admin_group.fieldtypes.ezrichtext.toolbars" cannot contain Custom Tag "video". Inline Custom Tags are not allowed in Toolbars other than "text".');
        $this->load($config);
    }

    public function testAllowsInlineCustomTagsInTextToolbar(): void
    {
        $config = Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/ezrichtext.yaml')
        );
        $config['custom_tags']['video']['is_inline'] = true;
        $this->container->setParameter('ezpublish.siteaccess.list', ['admin_group']);
        $this->container->setParameter("ezsettings.admin_group.fieldtypes.ezrichtext.toolbars", [
            'text' => [
                'buttons' => [
                    'video' => [
                        'priority' => 5,
                        'visible' => true,
                    ],
                ],
            ],
        ]);

        $this->load($config);
    }
}
