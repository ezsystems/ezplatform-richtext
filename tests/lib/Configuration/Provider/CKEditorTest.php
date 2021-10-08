<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration\Provider;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\Configuration\Provider\CKEditor;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType\RichText;

final class CKEditorTest extends BaseProviderTestCase
{
    public function createProvider(
        ConfigResolverInterface $configResolver = null,
        array $customStylesConfiguration = []
    ): Provider {
        return new CKEditor(
            $configResolver ?? $this->configResolver,
            $customStylesConfiguration
        );
    }

    public function getExpectedProviderName(): string
    {
        return 'CKEditor';
    }

    /**
     * @covers \EzSystems\Tests\EzPlatformRichText\Configuration\Provider\CKEditorTest::createProvider
     */
    public function testGetConfiguration(): void
    {
        $provider = $this->createProvider();

        $this->configResolver
            ->expects($this->exactly(1))
            ->method('hasParameter')
            ->willReturn(false);

        self::assertEquals(
            [
                'toolbar' => [],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfiguration(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar_group' => [
                    'visible' => true,
                    'buttons' => [
                        'test_button' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'toolbar' => ['test_button'],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationPriority(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar_group' => [
                    'visible' => true,
                    'priority' => 10,
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'toolbar' => ['test_button_first', 'test_button_middle', 'test_button_last'],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationPriorityWithGroups(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar_group_1' => [
                    'visible' => true,
                    'priority' => 10,
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
                'test_toolbar_group_2' => [
                    'visible' => true,
                    'priority' => 5,
                    'buttons' => [
                        'test2_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test2_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test2_button_middle' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'toolbar' => [
                    'test_button_first', 'test_button_middle', 'test_button_last', '|',
                    'test2_button_first', 'test2_button_middle', 'test2_button_last',
                ],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationVisibility(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar_group' => [
                    'visible' => true,
                    'priority' => 10,
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => false,
                            'priority' => 0,
                        ],
                    ],
                ],
                'custom_tags_group' => [
                    'visible' => false,
                    'priority' => 130,
                    'buttons' => [
                        'custom_tag_1' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'toolbar' => ['test_button_first', 'test_button_last'],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationVisibilityWithGroups(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar_group_1' => [
                    'visible' => true,
                    'priority' => 10,
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => false,
                            'priority' => 0,
                        ],
                    ],
                ],
                'test_toolbar_group_2' => [
                    'visible' => false,
                    'priority' => 20,
                    'buttons' => [
                        'test2_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                    ],
                ],
                'test_toolbar_group_3' => [
                    'visible' => true,
                    'priority' => 30,
                    'buttons' => [
                        'test3_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test3_button_second' => [
                            'visible' => true,
                            'priority' => 110,
                        ],
                    ],
                ],
                'custom_tags_group' => [
                    'visible' => true,
                    'priority' => 130,
                    'buttons' => [
                        'custom_tag_1' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'custom_tag_2' => [
                            'visible' => false,
                            'priority' => 80,
                        ],
                        'custom_tag_3' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'custom_tag_4' => [
                            'visible' => true,
                            'priority' => 120,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'toolbar' => [
                    'custom_tag_4', 'custom_tag_1', 'custom_tag_3', '|',
                    'test3_button_second', 'test3_button_first', '|', 'test_button_first', 'test_button_last',
                ],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationVisibilityWithCustomStyleInline(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnOnConsecutiveCalls(
                [
                    'test_toolbar_group_1' => [
                        'visible' => true,
                        'priority' => 10,
                        'buttons' => [
                            'test_button_first' => [
                                'visible' => true,
                                'priority' => 100,
                            ],
                            'ibexaCustomStyleInline' => [
                                'visible' => true,
                                'priority' => 80,
                            ],
                        ],
                    ],
                    'test_toolbar_group_2' => [
                        'visible' => true,
                        'priority' => 20,
                        'buttons' => [
                            'test2_button_first' => [
                                'visible' => true,
                                'priority' => 100,
                            ],
                        ],
                    ],
                ],
                ['custom_style_1', 'custom_style_2'],
            );

        $customStylesConfiguration = [
            'custom_style_1' => [
                'inline' => true,
            ],
            'custom_style_2' => [
                'inline' => false,
            ],
        ];

        $provider = $this->createProvider($configResolver, $customStylesConfiguration);

        self::assertEquals(
            [
                'toolbar' => [
                    'test2_button_first', '|', 'test_button_first', 'ibexaCustomStyleInline',
                ],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationVisibilityWithNoCustomStyleInline(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnOnConsecutiveCalls(
                [
                    'test_toolbar_group_1' => [
                        'visible' => true,
                        'priority' => 10,
                        'buttons' => [
                            'test_button_first' => [
                                'visible' => true,
                                'priority' => 100,
                            ],
                            'ibexaCustomStyleInline' => [
                                'visible' => true,
                                'priority' => 80,
                            ],
                        ],
                    ],
                    'test_toolbar_group_2' => [
                        'visible' => true,
                        'priority' => 20,
                        'buttons' => [
                            'test2_button_first' => [
                                'visible' => true,
                                'priority' => 100,
                            ],
                        ],
                    ],
                ],
                ['custom_style_1', 'custom_style_2'],
            );

        $customStylesConfiguration = [
            'custom_style_1' => [
                'inline' => false,
            ],
            'custom_style_2' => [
                'inline' => false,
            ],
        ];

        $provider = $this->createProvider($configResolver, $customStylesConfiguration);

        self::assertEquals(
            [
                'toolbar' => [
                    'test2_button_first', '|', 'test_button_first',
                ],
            ],
            $provider->getConfiguration()
        );
    }

    private function buildTestToolbarReturnCallback(array $buttonsConfig): callable
    {
        return static function (string $paramName) use ($buttonsConfig): ?array {
            $map = [
                RichText::TOOLBARS_SA_SETTINGS_ID => $buttonsConfig,
            ];

            return $map[$paramName] ?? null;
        };
    }
}
