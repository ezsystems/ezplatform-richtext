<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute;

use RuntimeException;

abstract class Attribute
{
    private const TYPE_STRING = 'string';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_CHOICE = 'choice';
    private const TYPE_NUMBER = 'number';
    private const TYPE_LINK = 'link';

    /** @var string */
    private $name;

    /** @var bool */
    private $required;

    /** @var mixed */
    private $defaultValue;

    public function __construct(string $name, bool $required = false, $defaultValue = null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public static function createFromConfig(string $name, array $config): self
    {
        switch ($config['type']) {
            case self::TYPE_STRING:
                return StringAttribute::createFromConfig($name, $config);
            case self::TYPE_BOOLEAN:
                return BooleanAttribute::createFromConfig($name, $config);
            case self::TYPE_CHOICE:
                return ChoiceAttribute::createFromConfig($name, $config);
            case self::TYPE_NUMBER:
                return NumberAttribute::createFromConfig($name, $config);
            case self::TYPE_LINK:
                return LinkAttribute::createFromConfig($name, $config);
            default:
                throw new RuntimeException('Unknown attribute type: ' . $config['$type']);
        }
    }
}
