<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @TODO: Describe purpose
 */
class InlineCustomTagToolbarGuardPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $customTags = $this->getInlineCustomTags($container);
        foreach ($this->getToolbarsBySiteAccess($container) as $siteAccess => $toolbar) {
            foreach ($toolbar as $toolbarName => $toolbarContent) {
                if ($toolbarName === 'text') {
                    continue;
                }

                $buttons = $toolbarContent['buttons'];
                foreach ($buttons as $buttonName => $buttonConfig) {
                    if (in_array($buttonName, $customTags, true)) {
                        throw new InvalidConfigurationException(
                            sprintf(
                                'Toolbar "%s" configured in "%s" cannot contain Custom Tag "%s". Inline Custom Tags are not allowed in Toolbars other than "%s".',
                                $toolbarName,
                                $siteAccess,
                                $buttonName,
                                'text'
                            )
                        );
                    }
                }
            }
        }
    }

    private function getToolbarsBySiteAccess(ContainerBuilder $container): \Traversable
    {
        foreach ($container->getParameterBag()->all() as $paramName => $parameter) {
            if (str_contains($paramName, RichText::TOOLBARS_SA_SETTINGS_ID)) {
                yield $paramName => $parameter;
            }
        }
    }

    /**
     * @return string[]
     */
    private function getInlineCustomTags(ContainerBuilder $container): array
    {
        if (!$container->hasParameter('ezplatform.ezrichtext.custom_tags')) {
            return [];
        }
        $customTags = $container->getParameter('ezplatform.ezrichtext.custom_tags');

        $customTags = array_filter($customTags, static function (array $customTag): bool {
            return $customTag['is_inline'] ?? false;
        });

        return array_keys($customTags);
    }
}