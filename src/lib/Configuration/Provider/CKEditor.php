<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\Provider;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;
use EzSystems\EzPlatformRichTextBundle\DependencyInjection\Configuration\Parser\FieldType\RichText;

/**
 * CKEditor configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class CKEditor implements Provider
{
    private const SEPARATOR = '|';
    private const CUSTOM_TAGS_GROUP_KEY = 'custom_tags_group';

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $this->configResolver = $configResolver;
    }

    public function getName(): string
    {
        return 'CKEditor';
    }

    /**
     * Returns CKEditor configuration.
     * @phpstan-return array<array-key, array{
     *  toolbars: array<string>,
     *  customTags: array<string>,
     * }>
     */
    public function getConfiguration(): array
    {
        return [
            'toolbars' => $this->getToolbars(),
            'customTags' => $this->getCustomTags(),
        ];
    }

    /**
     * Returns toolbars configuration.
     * @phpstan-return array<string>
     */
    private function getToolbars(): array
    {
        $toolbarsByGroupsConfiguration = $this->getSiteAccessConfigArray(RichText::TOOLBARS_SA_SETTINGS_ID);

        $toolbarsByGroupsConfiguration = array_filter(
            $toolbarsByGroupsConfiguration,
            static function (string $key): bool {
                return $key !== self::CUSTOM_TAGS_GROUP_KEY;
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->filterToolbars($toolbarsByGroupsConfiguration);
    }

    /**
     * Returns customTags configuration.
     * @phpstan-return array<string>
     */
    private function getCustomTags(): array
    {
        $toolbarsByGroupsConfiguration = $this->getSiteAccessConfigArray(RichText::TOOLBARS_SA_SETTINGS_ID);

        $toolbarsByGroupsConfiguration = array_filter(
            $toolbarsByGroupsConfiguration,
            static function (string $key): bool {
                return $key === self::CUSTOM_TAGS_GROUP_KEY;
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->filterToolbars($toolbarsByGroupsConfiguration);
    }

    /**
     * Returns filtered Toolbars configuration.
     * @phpstan-return array<string>
     */
    private function filterToolbars(
        array $toolbarsByGroupsConfiguration = []
    ): array {
        $toolbars = [];

        $toolbarsByGroupsConfiguration = array_filter(
            $toolbarsByGroupsConfiguration,
            static function (array $value): bool {
                return $value['visible'];
            }
        );

        uasort($toolbarsByGroupsConfiguration, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        foreach ($toolbarsByGroupsConfiguration as $configuration) {
            $toolbarButtons = $this->getToolbarButtons($configuration['buttons'] ?? []);

            if (count($toolbarButtons)) {
                $toolbars = array_merge(
                    $toolbars,
                    $toolbarButtons,
                    [self::SEPARATOR],
                );
            }
        }

        array_pop($toolbars);

        return array_values($toolbars);
    }

    /**
     * Returns List of visible, sorted buttons.
     * @phpstan-return array<string>
     */
    private function getToolbarButtons(array $buttons): array
    {
        $buttons = array_filter($buttons, static function (array $value): bool {
            return $value['visible'];
        });

        uasort($buttons, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        return array_keys($buttons);
    }

    /**
     * Get configuration array from the SiteAccess-aware configuration, checking first for its existence.
     */
    private function getSiteAccessConfigArray(string $paramName): array
    {
        return $this->configResolver->hasParameter($paramName)
            ? $this->configResolver->getParameter($paramName)
            : [];
    }
}
