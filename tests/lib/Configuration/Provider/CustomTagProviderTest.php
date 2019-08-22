<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration\Provider;

use EzSystems\EzPlatformRichText\Configuration\Provider\CustomTag;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

class CustomTagProviderTest extends BaseCustomTemplateProviderTestCase
{
    public function createProvider(): Provider
    {
        return new CustomTag($this->configResolver, $this->mapper);
    }

    public function getExpectedProviderName(): string
    {
        return 'customTags';
    }

    protected function getExpectedCustomTemplatesConfiguration(): array
    {
        return ['tag' => ['template' => 'tag.html.twig', 'attributes' => []]];
    }

    protected function getCustomTemplateSiteAccessConfigParamName(): string
    {
        return 'fieldtypes.ezrichtext.custom_tags';
    }
}
