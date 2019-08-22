<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration;

use EzSystems\EzPlatformRichText\Configuration\AggregateProvider;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EzSystems\EzPlatformRichText\Configuration\AggregateProvider
 */
class AggregateProviderTest extends TestCase
{
    /**
     * @covers \EzSystems\EzPlatformRichText\Configuration\AggregateProvider::getConfiguration
     *
     * @dataProvider getConfiguration
     *
     * @param array $configuration
     */
    public function testGetConfiguration(array $configuration): void
    {
        $providers = [];
        foreach ($configuration as $providerName => $providerConfiguration) {
            $providers[] = new class($providerName, $providerConfiguration) implements Provider {
                private $name;
                private $configuration;

                public function __construct(string $name, array $configuration)
                {
                    $this->name = $name;
                    $this->configuration = $configuration;
                }

                public function getName(): string
                {
                    return $this->name;
                }

                public function getConfiguration(): array
                {
                    return $this->configuration;
                }
            };
        }

        $providerService = new AggregateProvider($providers);

        self::assertEquals($configuration, $providerService->getConfiguration());
    }

    public function getConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'noConfigProvider' => [],
                ],
            ],
            [
                [
                    'provider1' => [
                        'provider1_key1' => 'provider1_key1_value1',
                        'provider1_key2' => 'provider1_key2_value2',
                    ],
                    'provider2' => [
                        'provider2_key1' => 'provider2_key1_value1',
                        'provider2_key2' => 'provider2_key2_value2',
                    ],
                ],
            ],
            [
                [
                    'provider1' => [1, 2, 3],
                    'provider2' => [1, 2, 3],
                ],
            ],
        ];
    }
}
