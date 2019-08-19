<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration;

use EzSystems\EzPlatformRichText\API\Configuration\ProviderService;

/**
 * RichText configuration provider, providing configuration by aggregating different sources.
 *
 * @internal type-hint \EzSystems\EzPlatformRichText\API\Configuration\ProviderService
 */
final class AggregateProvider implements ProviderService
{
    /** @var \EzSystems\EzPlatformRichText\SPI\Configuration\Provider[]|iterable */
    private $providers;

    /**
     * @param \EzSystems\EzPlatformRichText\SPI\Configuration\Provider[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        foreach ($this->providers as $provider) {
            $configuration[$provider->getName()] = $provider->getConfiguration();
        }

        return $configuration;
    }
}
