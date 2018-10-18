<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText;

/**
 * Abstract class for XML normalization of string input.
 */
abstract class Normalizer
{
    /**
     * Check if normalizer accepts given $input for normalization.
     *
     * @param string $input
     *
     * @return bool
     */
    abstract public function accept($input);

    /**
     * Normalizes given $input and returns the result.
     *
     * @param string $input
     *
     * @return string
     */
    abstract public function normalize($input);
}
