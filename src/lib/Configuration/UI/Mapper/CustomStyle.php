<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\UI\Mapper;

use RuntimeException;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RichText Custom Style configuration mapper.
 *
 * @internal For internal use by RichText package
 */
final class CustomStyle implements CustomTemplateConfigMapper
{
    /** @var array */
    private $customStylesConfiguration;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    private $translator;

    /** @var \Symfony\Component\Asset\Packages */
    private $packages;

    /** @var string */
    private $translationDomain;

    public function __construct(
        array $customStylesConfiguration,
        TranslatorInterface $translator,
        string $translationDomain,
        Packages $packages
    ) {
        $this->customStylesConfiguration = $customStylesConfiguration;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
        $this->packages = $packages;
    }

    /**
     * Map Configuration for the given list of enabled Custom Styles.
     *
     * @param array $enabledCustomStyles
     *
     * @return array Mapped configuration
     */
    public function mapConfig(array $enabledCustomStyles): array
    {
        $config = [];
        foreach ($enabledCustomStyles as $styleName) {
            if (!isset($this->customStylesConfiguration[$styleName])) {
                throw new RuntimeException(
                    "RichText Custom Style configuration for {$styleName} not found."
                );
            }

            $customStyleConfiguration = $this->customStylesConfiguration[$styleName];
            $config[$styleName]['inline'] = $customStyleConfiguration['inline'];
            $config[$styleName]['label'] = $this->translator->trans(
                /** @Ignore */
                sprintf('ezrichtext.custom_styles.%s.label', $styleName),
                [],
                $this->translationDomain
            );
        }

        return $config;
    }
}
