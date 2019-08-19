<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\API\Configuration;

/**
 * RichText configuration provider API.
 *
 * To provide custom configuration implement \EzSystems\EzPlatformRichText\SPI\Configuration\Provider
 * instead.
 *
 * @see \EzSystems\EzPlatformRichText\SPI\Configuration\Provider
 */
interface ProviderService
{
    /**
     * Provide RichText package configuration in the form of associative multidimensional array.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
