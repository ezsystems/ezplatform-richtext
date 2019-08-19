<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\Provider;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomStyle as CustomStyleConfigurationMapper;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

/**
 * Custom Styles configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class CustomStyle implements Provider
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomStyle */
    private $customStyleConfigurationMapper;

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomStyle $customStyleConfigurationMapper
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        CustomStyleConfigurationMapper $customStyleConfigurationMapper
    ) {
        $this->configResolver = $configResolver;
        $this->customStyleConfigurationMapper = $customStyleConfigurationMapper;
    }

    public function getName(): string
    {
        return 'customStyles';
    }

    /**
     * @return array RichText Custom Styles config
     */
    public function getConfiguration(): array
    {
        if ($this->configResolver->hasParameter('fieldtypes.ezrichtext.custom_styles')) {
            return $this->customStyleConfigurationMapper->mapConfig(
                $this->configResolver->getParameter('fieldtypes.ezrichtext.custom_styles')
            );
        }

        return [];
    }
}
