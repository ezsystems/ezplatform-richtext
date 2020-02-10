<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute;

final class LinkAttribute extends Attribute
{
    public static function createFromConfig(string $name, array $config): Attribute
    {
        return new self($name, $config['required'] ?? false, $config['default_value'] ?? null);
    }
}
