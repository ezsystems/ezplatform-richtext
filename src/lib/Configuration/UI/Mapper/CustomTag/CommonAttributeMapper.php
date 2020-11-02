<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag;

/**
 * Map RichText Custom Tag attribute of any type to proper UI config.
 *
 * @internal For internal use by RichText package
 */
class CommonAttributeMapper implements AttributeMapper
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $tagName, string $attributeName, string $attributeType): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function mapConfig(
        string $tagName,
        string $attributeName,
        array $customTagAttributeProperties
    ): array {
        return [
            'label' => "ezrichtext.custom_tags.{$tagName}.attributes.{$attributeName}.label",
            'type' => $customTagAttributeProperties['type'],
            'required' => $customTagAttributeProperties['required'],
            'defaultValue' => $customTagAttributeProperties['default_value'],
        ];
    }
}
