<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute;

final class ChoiceAttribute extends Attribute
{
    /** @var string[] */
    private $choices;

    public function __construct(string $name, array $choices = [], bool $required = false, $defaultValue = null)
    {
        parent::__construct($name, $required, $defaultValue);

        $this->choices = $choices;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public static function createFromConfig(string $name, array $config): Attribute
    {
        return new self(
            $name,
            $config['choices'] ?? [],
            $config['required'] ?? false,
            $config['default_value'] ?? null
        );
    }
}
