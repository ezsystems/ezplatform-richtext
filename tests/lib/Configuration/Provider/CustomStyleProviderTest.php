<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration\Provider;

use EzSystems\EzPlatformRichText\Configuration\Provider\CustomStyle;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

class CustomStyleProviderTest extends BaseCustomTemplateProviderTestCase
{
    public function createProvider(): Provider
    {
        return new CustomStyle($this->configResolver, $this->mapper);
    }

    public function getExpectedProviderName(): string
    {
        return 'customStyles';
    }

    protected function getExpectedCustomTemplatesConfiguration(): array
    {
        return ['paragraph' => ['style1']];
    }

    protected function getCustomTemplateSiteAccessConfigParamName(): string
    {
        return 'fieldtypes.ezrichtext.custom_styles';
    }
}
