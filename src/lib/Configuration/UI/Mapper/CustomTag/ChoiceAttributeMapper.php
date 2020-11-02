<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag;

/**
 * Map RichText Custom Tag attribute of 'choice' type to proper UI config.
 *
 * @internal For internal use by RichText package
 */
final class ChoiceAttributeMapper extends CommonAttributeMapper implements AttributeMapper
{
    public function supports(string $tagName, string $attributeName, string $attributeType): bool
    {
        return 'choice' === $attributeType;
    }

    /**
     * {@inheritdoc}
     */
    public function mapConfig(
        string $tagName,
        string $attributeName,
        array $customTagAttributeProperties
    ): array {
        $parentConfig = parent::mapConfig($tagName, $attributeName, $customTagAttributeProperties);

        $parentConfig['choices'] = $customTagAttributeProperties['choices'];
        $parentConfig['choicesLabel'] = [];

        foreach ($parentConfig['choices'] as $choice) {
            $parentConfig['choicesLabel'][$choice] = "ezrichtext.custom_tags.{$tagName}.attributes.{$attributeName}.choices.{$choice}.label";
        }

        return $parentConfig;
    }
}
