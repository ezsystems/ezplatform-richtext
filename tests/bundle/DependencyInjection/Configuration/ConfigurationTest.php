<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\DependencyInjection\Configuration;

use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class ConfigurationTest extends TestCase
{
    private const INPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/input/';
    private const OUTPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/output/';

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

        $configuration = new Configuration();
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
}
