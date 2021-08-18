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
     * @return array CKEditor config
     */
    public function getConfiguration(): array
    {
        return [
            'toolbars' => $this->getToolbars(),
        ];
    }

    /**
     * @return array Toolbars configuration
     */
    private function getToolbars(): array
    {
        $toolbarsByGroupsConfiguration = $this->getSiteAccessConfigArray(RichText::TOOLBARS_SA_SETTINGS_ID);
        $toolbars = [];

        $toolbarsByGroupsConfiguration = array_filter($toolbarsByGroupsConfiguration, static function (array $value): bool {
            return $value['visible'];
        });

        uasort($toolbarsByGroupsConfiguration, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        foreach ($toolbarsByGroupsConfiguration as $groupName => $configuration) {
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
     * @return string[] List of visible buttons
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
     *
     * @param string $paramName
     *
     * @return array
     */
    private function getSiteAccessConfigArray(string $paramName): array
    {
        return $this->configResolver->hasParameter($paramName)
            ? $this->configResolver->getParameter($paramName)
            : [];
    }
}
