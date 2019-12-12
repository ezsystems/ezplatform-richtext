<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\DependencyInjection\Configuration;

use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    private const INPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/input/';
    private const OUTPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/output/';

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @dataProvider providerForTestProcessingConfiguration
     */
    public function testProcessingConfiguration(SplFileInfo $inputConfigurationFile): void
    {
        $outputFilePath = self::OUTPUT_FIXTURES_DIR . $inputConfigurationFile->getFilename();
        if (!file_exists($outputFilePath)) {
            $this->markTestIncomplete("Missing output fixture: {$outputFilePath}");
        }

        $configs = [Yaml::parseFile($inputConfigurationFile->getPathname())];
        $expectedProcessedConfiguration = Yaml::parseFile($outputFilePath);

        $configuration = $this->getConfiguration();
        $processor = new Processor();
        $processedConfiguration = $processor->processConfiguration($configuration, $configs);

        self::assertEquals(
            $expectedProcessedConfiguration,
            $processedConfiguration
        );
    }

    public function providerForTestProcessingConfiguration(): iterable
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in(self::INPUT_FIXTURES_DIR)
            ->name('*.yaml')
            ->sortByName()
        ;

        foreach ($finder as $file) {
            yield [$file];
        }
    }

    /**
     * Data provider for testMergingAlloyEditorConfiguration.
     *
     * @see testMergingAlloyEditorConfiguration
     *
     * @return array
     */
    public function providerForTestMergingAlloyEditorConfiguration(): array
    {
        return [
            'Empty configuration' => [
                [],
                [
                    'custom_tags' => [],
                    'custom_styles' => [],
                ],
            ],
            'Alloy editor configs from multiple sources' => [
                // input configs
                [
                    [
                        'alloy_editor' => [
                            'extra_plugins' => ['plugin1'],
                            'extra_buttons' => [
                                'paragraph' => ['button1', 'button2'],
                                'embed' => ['button1'],
                            ],
                        ],
                    ],
                    [
                        'alloy_editor' => [
                            'extra_plugins' => ['plugin2'],
                            'extra_buttons' => [
                                'paragraph' => ['button3', 'button4'],
                                'embed' => ['button1'],
                            ],
                        ],
                    ],
                ],
                // expected merged config
                [
                    'alloy_editor' => [
                        'extra_buttons' => [
                            'paragraph' => ['button1', 'button2', 'button3', 'button4'],
                            'embed' => ['button1', 'button1'],
                        ],
                        'extra_plugins' => ['plugin1', 'plugin2'],
                    ],
                    'custom_tags' => [],
                    'custom_styles' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestMergingAlloyEditorConfiguration
     *
     * @param array $configurationValues
     * @param array $expectedProcessedConfiguration
     */
    public function testMergingAlloyEditorConfiguration(
        array $configurationValues,
        array $expectedProcessedConfiguration
    ): void {
        $this->assertProcessedConfigurationEquals($configurationValues, $expectedProcessedConfiguration);
    }
}
