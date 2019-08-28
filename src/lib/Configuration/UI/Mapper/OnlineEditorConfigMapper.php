<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\Configuration\UI\Mapper;

/**
 * Contracts for mapping Semantic configuration to settings exposed to templates.
 *
 * @internal For internal use for RichText package
 */
interface OnlineEditorConfigMapper
{
    /**
     * Map Online Editor custom CSS classes configuration.
     *
     * @param array $semanticSemanticConfiguration
     *
     * @return array
     */
    public function mapCssClassesConfiguration(array $semanticSemanticConfiguration): array;

    /**
     * Map Online Editor custom data attributes classes configuration.
     *
     * @param array $semanticConfiguration
     *
     * @return array
     */
    public function mapDataAttributesConfiguration(array $semanticConfiguration): array;
}
