<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\SPI\Configuration;

/**
 * RichText configuration provider.
 */
interface Provider
{
    /**
     * Get configuration provider name.
     *
     * Should consist of letters, numbers, dashes, and underscores only.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get configuration settings as an associative array.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
