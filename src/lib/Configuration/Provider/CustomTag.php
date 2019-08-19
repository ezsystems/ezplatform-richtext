<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\Provider;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag as CustomTagConfigurationMapper;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

/**
 * Custom Tags configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class CustomTag implements Provider
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag */
    private $customTagConfigurationMapper;

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag $customTagConfigurationMapper
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        CustomTagConfigurationMapper $customTagConfigurationMapper
    ) {
        $this->configResolver = $configResolver;
        $this->customTagConfigurationMapper = $customTagConfigurationMapper;
    }

    public function getName(): string
    {
        return 'customTags';
    }

    /**
     * @return array RichText Custom Tags config
     */
    public function getConfiguration(): array
    {
        if ($this->configResolver->hasParameter('fieldtypes.ezrichtext.custom_tags')) {
            return $this->customTagConfigurationMapper->mapConfig(
                $this->configResolver->getParameter('fieldtypes.ezrichtext.custom_tags')
            );
        }

        return [];
    }
}
